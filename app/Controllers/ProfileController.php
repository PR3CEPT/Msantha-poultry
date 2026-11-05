<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
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

        return View::render('profile/index', [
            'pageTitle' => 'My Profile & Security',
            'activeNav' => 'profile',
            'user' => $user,
            'roleData' => $roleData,
            'auditLogs' => $auditLogs,
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

        $stmt = $pdo->prepare("
            UPDATE users SET
                full_name = ?,
                phone = ?
            WHERE id = ?
        ");
        $stmt->execute([$fullName, $normalizedPhone, $user['id']]);

        // Refresh session user data
        $_SESSION['user']['full_name'] = $fullName;
        $_SESSION['user']['phone'] = $normalizedPhone;
        Auth::reset();

        Audit::log('update_profile', 'users', (int) $user['id'], [
            'full_name' => $user['full_name'],
            'phone' => $user['phone'],
        ], [
            'full_name' => $fullName,
            'phone' => $normalizedPhone,
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

        $update = $pdo->prepare("
            UPDATE users SET
                password_hash = ?,
                must_change_password = 0
            WHERE id = ?
        ");
        $update->execute([$newHash, $user['id']]);

        Auth::reset();

        Audit::log('profile_password_change', 'users', (int) $user['id'], null, [
            'changed_at' => date('Y-m-d H:i:s'),
        ]);

        $_SESSION['flash_success'] = 'Your password has been changed successfully.';
        return Response::redirect(url('/profile#security'));
    }
}
