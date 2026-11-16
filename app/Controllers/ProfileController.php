<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Cipher;
use App\Core\DB;
use App\Core\Phone;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;
use PDO;
use Throwable;

class ProfileController
{
    /**
     * Display user profile and activity.
     * Route: GET /profile
     */
    public function index(Request $request): Response
    {
        Auth::requireLogin();

        $user = Auth::user();
        $pdo = DB::pdo();

        // Fetch recent audit activities for this user
        $auditLogs = [];
        try {
            $stmt = $pdo->prepare("
                SELECT id, action, table_name, record_id, ip_address, created_at, details
                FROM audit_log
                WHERE user_id = ?
                ORDER BY id DESC
                LIMIT 15
            ");
            $stmt->execute([$user['id']]);
            $auditLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $auditLogs = [];
        }

        // Get role details
        $roleInfo = $pdo->prepare("SELECT name FROM roles WHERE id = ? LIMIT 1");
        $roleInfo->execute([$user['role_id']]);
        $roleName = $roleInfo->fetchColumn() ?: ucfirst((string) ($user['role_code'] ?? 'user'));
        $roleData = ['name' => $roleName, 'description' => 'Staff access and role permissions'];

        // Fetch any pending username change request
        $pendingUsernameRequest = null;
        try {
            $reqStmt = $pdo->prepare("SELECT * FROM username_change_requests WHERE user_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
            $reqStmt->execute([$user['id']]);
            $pendingUsernameRequest = $reqStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable) {
            $pendingUsernameRequest = null;
        }

        // Decrypt password for viewing in user profile
        $userPasswordDisplay = Cipher::decrypt($user['password_display'] ?? null);

        return View::render('profile/index', [
            'pageTitle' => 'My Profile & Security',
            'activeNav' => 'profile',
            'user' => $user,
            'roleData' => $roleData,
            'auditLogs' => $auditLogs,
            'pendingUsernameRequest' => $pendingUsernameRequest,
            'userPasswordDisplay' => $userPasswordDisplay,
        ]);
    }

    /**
     * Update user personal information.
     * Route: POST /profile
     */
    public function update(Request $request): Response
    {
        Auth::requireLogin();
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'full_name' => 'required|min:3|max:100',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = $validator->firstError();
            return Response::redirect(url('/profile'));
        }

        $fullName = trim((string) $request->input('full_name'));
        $phoneRaw = trim((string) $request->input('phone'));
        $normalizedPhone = null;

        if ($phoneRaw !== '') {
            $normalizedPhone = Phone::normalize($phoneRaw);
            if ($normalizedPhone === null) {
                $_SESSION['flash_error'] = 'Please provide a valid Malawi mobile number (e.g. 0999899620 or +265...).';
                return Response::redirect(url('/profile'));
            }
        }

        $pdo = DB::pdo();

        // Check if phone is already taken by another user
        if ($normalizedPhone !== null) {
            $chk = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ? LIMIT 1");
            $chk->execute([$normalizedPhone, $user['id']]);
            if ($chk->fetch()) {
                $_SESSION['flash_error'] = 'That phone number is already registered to another user account.';
                return Response::redirect(url('/profile'));
            }
        }

        $email = trim((string) $request->input('email'));
        if ($email === '') {
            $email = null;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Please provide a valid email address.';
            return Response::redirect(url('/profile'));
        }

        $stmt = $pdo->prepare("
            UPDATE users SET
                full_name = ?,
                phone = ?,
                email = ?
            WHERE id = ?
        ");
        $stmt->execute([$fullName, $normalizedPhone, $email, $user['id']]);

        // Refresh session user data
        $_SESSION['user']['full_name'] = $fullName;
        $_SESSION['user']['phone'] = $normalizedPhone;
        $_SESSION['user']['email'] = $email;
        Auth::reset();

        Audit::log('update_profile', 'users', (int) $user['id'], [
            'full_name' => $user['full_name'],
            'phone' => $user['phone'],
            'email' => $user['email'] ?? null,
        ], [
            'full_name' => $fullName,
            'phone' => $normalizedPhone,
            'email' => $email,
        ]);

        $_SESSION['flash_success'] = 'Your profile details have been updated successfully.';
        return Response::redirect(url('/profile'));
    }

    /**
     * Change user password from profile.
     * Route: POST /profile/password
     */
    public function changePassword(Request $request): Response
    {
        Auth::requireLogin();
        $user = Auth::user();

        $currentPassword = (string) $request->input('current_password');
        $newPassword = (string) $request->input('new_password');
        $confirmPassword = (string) $request->input('new_password_confirmation');

        if ($currentPassword === '' || $newPassword === '') {
            $_SESSION['flash_error'] = 'Please enter your current password and your new password.';
            return Response::redirect(url('/profile#security'));
        }

        if (strlen($newPassword) < 8) {
            $_SESSION['flash_error'] = 'The new password must be at least 8 characters long.';
            return Response::redirect(url('/profile#security'));
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['flash_error'] = 'The new password and confirmation password do not match.';
            return Response::redirect(url('/profile#security'));
        }

        $pdo = DB::pdo();
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user['id']]);
        $existingHash = (string) $stmt->fetchColumn();

        if (!password_verify($currentPassword, $existingHash)) {
            $_SESSION['flash_error'] = 'Your current password is incorrect.';
            return Response::redirect(url('/profile#security'));
        }

        $newHash = password_hash($newPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 2048,
            'time_cost' => 2,
            'threads' => 1,
        ]);
        $cipherPass = Cipher::encrypt($newPassword);

        $update = $pdo->prepare("
            UPDATE users SET
                password_hash = ?,
                password_display = ?,
                must_change_password = 0
            WHERE id = ?
        ");
        $update->execute([$newHash, $cipherPass, $user['id']]);

        Auth::reset();

        Audit::log('profile_password_change', 'users', (int) $user['id'], null, [
            'changed_at' => date('Y-m-d H:i:s'),
        ]);

        $_SESSION['flash_success'] = 'Your password has been changed successfully.';
        return Response::redirect(url('/profile#security'));
    }

    /**
     * Submit a username change request for Managing Director review.
     * Route: POST /profile/request-username-change
     */
    public function requestUsernameChange(Request $request): Response
    {
        Auth::requireLogin();
        $user = Auth::user();

        $requestedUsername = trim((string) $request->input('requested_username'));
        $reason = trim((string) $request->input('reason', ''));

        if ($requestedUsername === '') {
            $_SESSION['flash_error'] = 'Please enter your desired new username.';
            return Response::redirect(url('/profile'));
        }

        if (!preg_match('/^[a-zA-Z0-9._]{3,30}$/', $requestedUsername)) {
            $_SESSION['flash_error'] = 'Username must be between 3 and 30 characters and contain only letters, numbers, dots, and underscores.';
            return Response::redirect(url('/profile'));
        }

        if (strtolower($requestedUsername) === strtolower((string)$user['username'])) {
            $_SESSION['flash_error'] = 'The requested username is identical to your current username.';
            return Response::redirect(url('/profile'));
        }

        $pdo = DB::pdo();

        // Check if taken
        $check = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND id != ? LIMIT 1");
        $check->execute([$requestedUsername, $user['id']]);
        if ($check->fetch()) {
            $_SESSION['flash_error'] = "The username '@{$requestedUsername}' is already taken by another account.";
            return Response::redirect(url('/profile'));
        }

        // Check existing pending request
        $existing = $pdo->prepare("SELECT id FROM username_change_requests WHERE user_id = ? AND status = 'pending' LIMIT 1");
        $existing->execute([$user['id']]);
        if ($existing->fetch()) {
            $_SESSION['flash_error'] = 'You already have a pending username change request awaiting Managing Director approval.';
            return Response::redirect(url('/profile'));
        }

        $stmt = $pdo->prepare("
            INSERT INTO username_change_requests (user_id, current_username, requested_username, reason, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $user['id'],
            $user['username'],
            $requestedUsername,
            $reason !== '' ? $reason : null,
        ]);

        Audit::log('username_change_requested', 'users', (int) $user['id'], [
            'current_username' => $user['username'],
        ], [
            'requested_username' => $requestedUsername,
            'reason' => $reason,
        ]);

        $_SESSION['flash_success'] = "Username change request for '@{$requestedUsername}' submitted to Managing Director for review.";
        return Response::redirect(url('/profile'));
    }
}
