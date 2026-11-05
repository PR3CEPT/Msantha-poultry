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
use Throwable;

class AuthController
{
    public function showLogin(Request $request): Response
    {
        if (Auth::check()) {
            return Response::redirect(url('/'));
        }

        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        return View::render('auth/login', [
            'pageTitle' => 'Login — Msantha Poultry',
            'flashError' => $error,
            'flashSuccess' => $success,
        ], null); // Plain full page without dashboard layout
    }

    public function login(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = $validator->firstError();
            return Response::redirect(url('/login'));
        }

        $identifier = (string) $request->input('username');
        $password = (string) $request->input('password');

        try {
            $success = Auth::attempt($identifier, $password);
            if (!$success) {
                $_SESSION['flash_error'] = 'Invalid username/phone or password.';
                return Response::redirect(url('/login'));
            }

            if (Auth::mustChangePassword()) {
                $_SESSION['flash_info'] = 'Please choose your personal password to continue.';
                return Response::redirect(url('/force-password-change'));
            }

            $_SESSION['flash_success'] = 'Welcome back!';
            return Response::redirect(url('/'));
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            return Response::redirect(url('/login'));
        }
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        $_SESSION['flash_success'] = 'You have been logged out.';
        return Response::redirect(url('/login'));
    }

    public function showForgotPassword(Request $request): Response
    {
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        return View::render('auth/forgot-password', [
            'pageTitle' => 'Forgot Password — Msantha Poultry',
            'flashError' => $error,
            'flashSuccess' => $success,
        ], null);
    }

    public function sendResetCode(Request $request): Response
    {
        $identifier = trim((string) $request->input('identifier'));
        if ($identifier === '') {
            $_SESSION['flash_error'] = 'Please enter your username or registered phone number.';
            return Response::redirect(url('/forgot-password'));
        }

        $pdo = DB::pdo();
        $normalizedPhone = Phone::normalize($identifier);

        $stmt = $pdo->prepare("
            SELECT id, full_name, phone, username, is_active
            FROM users
            WHERE (username = :u OR (phone IS NOT NULL AND phone = :p))
            LIMIT 1
        ");
        $stmt->execute(['u' => $identifier, 'p' => $normalizedPhone]);
        $user = $stmt->fetch();

        if (!$user || empty($user['phone'])) {
            // Do not disclose whether user exists, but give generic message
            $_SESSION['flash_error'] = 'If an active account exists with that identifier and phone, a reset code was sent.';
            return Response::redirect(url('/forgot-password'));
        }

        if ((int) $user['is_active'] !== 1) {
            $_SESSION['flash_error'] = 'Account is inactive. Please contact the owner.';
            return Response::redirect(url('/forgot-password'));
        }

        // Generate 6-digit random code
        $code = (string) random_int(100000, 999999);
        $codeHash = hash('sha256', $code);

        $_SESSION['password_reset'] = [
            'user_id' => (int) $user['id'],
            'phone' => $user['phone'],
            'code_hash' => $codeHash,
            'expires_at' => time() + 600, // 10 minutes
            'attempts_left' => 3,
            'verified' => false,
        ];

        // Queue SMS in sms_outbox
        $smsBody = "Msantha Poultry: Your password reset code is {$code}. It expires in 10 minutes.";
        $pdo->prepare("
            INSERT INTO sms_outbox (to_phone, user_id, body, status, dedupe_key)
            VALUES (?, ?, ?, 'queued', ?)
        ")->execute([
            $user['phone'],
            $user['id'],
            $smsBody,
            'RESET:' . $user['id'] . ':' . time(),
        ]);

        $_SESSION['flash_success'] = 'A 6-digit reset code has been sent via SMS to ' . Phone::formatLocal($user['phone']);
        return Response::redirect(url('/verify-reset-code'));
    }

    public function showVerifyResetCode(Request $request): Response
    {
        if (empty($_SESSION['password_reset'])) {
            return Response::redirect(url('/forgot-password'));
        }

        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        return View::render('auth/verify-code', [
            'pageTitle' => 'Enter Reset Code — Msantha Poultry',
            'phone' => $_SESSION['password_reset']['phone'] ?? '',
            'flashError' => $error,
            'flashSuccess' => $success,
        ], null);
    }

    public function verifyResetCode(Request $request): Response
    {
        if (empty($_SESSION['password_reset'])) {
            $_SESSION['flash_error'] = 'Reset session expired. Please start over.';
            return Response::redirect(url('/forgot-password'));
        }

        $code = trim((string) $request->input('code'));
        $resetData = &$_SESSION['password_reset'];

        if (time() > $resetData['expires_at']) {
            unset($_SESSION['password_reset']);
            $_SESSION['flash_error'] = 'The reset code has expired (valid 10 minutes). Please request a new one.';
            return Response::redirect(url('/forgot-password'));
        }

        if ($resetData['attempts_left'] <= 0) {
            unset($_SESSION['password_reset']);
            $_SESSION['flash_error'] = 'Maximum verification attempts exceeded. Please request a new code.';
            return Response::redirect(url('/forgot-password'));
        }

        if (!hash_equals($resetData['code_hash'], hash('sha256', $code))) {
            $resetData['attempts_left']--;
            $left = $resetData['attempts_left'];
            $_SESSION['flash_error'] = "Invalid code. You have {$left} attempt(s) remaining.";
            return Response::redirect(url('/verify-reset-code'));
        }

        // Code is verified
        $resetData['verified'] = true;
        return Response::redirect(url('/reset-password'));
    }

    public function showResetPassword(Request $request): Response
    {
        if (empty($_SESSION['password_reset']['verified'])) {
            return Response::redirect(url('/forgot-password'));
        }

        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        return View::render('auth/reset-password', [
            'pageTitle' => 'Set New Password — Msantha Poultry',
            'flashError' => $error,
        ], null);
    }

    public function resetPassword(Request $request): Response
    {
        if (empty($_SESSION['password_reset']['verified'])) {
            return Response::redirect(url('/forgot-password'));
        }

        $userId = (int) $_SESSION['password_reset']['user_id'];
        $newPass = (string) $request->input('password');
        $confirm = (string) $request->input('password_confirmation');

        if (strlen($newPass) < 8) {
            $_SESSION['flash_error'] = 'Password must be at least 8 characters long.';
            return Response::redirect(url('/reset-password'));
        }

        if ($newPass !== $confirm) {
            $_SESSION['flash_error'] = 'Passwords do not match.';
            return Response::redirect(url('/reset-password'));
        }

        $hash = password_hash($newPass, PASSWORD_ARGON2ID);
        $pdo = DB::pdo();
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $userId]);

        Audit::log('update', 'users', $userId, null, ['action' => 'password_reset_by_sms'], $userId);

        unset($_SESSION['password_reset']);
        $_SESSION['flash_success'] = 'Password updated successfully. Please login with your new password.';
        return Response::redirect(url('/login'));
    }

    public function showChangePassword(Request $request): Response
    {
        Auth::requireRole(['owner', 'manager', 'worker', 'accountant', 'viewer']);

        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        return View::render('auth/change-password', [
            'pageTitle' => 'Change Password',
            'flashError' => $error,
            'flashSuccess' => $success,
            'activeNav' => 'more',
        ]);
    }

    public function showForcePasswordChange(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::redirect(url('/login'));
        }

        if (!Auth::mustChangePassword()) {
            return Response::redirect(url('/'));
        }

        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        return View::render('auth/force-change-password', [
            'pageTitle' => 'Set Permanent Password',
            'flashError' => $error,
        ], null);
    }

    public function forcePasswordChange(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::redirect(url('/login'));
        }

        $user = Auth::user();
        $userId = (int) $user['id'];

        $newPass = (string) $request->input('password');
        $confirm = (string) $request->input('password_confirmation');

        if (strlen($newPass) < 10) {
            $_SESSION['flash_error'] = 'Password must be at least 10 characters long.';
            return Response::redirect(url('/force-password-change'));
        }

        if ($newPass !== $confirm) {
            $_SESSION['flash_error'] = 'Passwords do not match.';
            return Response::redirect(url('/force-password-change'));
        }

        $hash = password_hash($newPass, PASSWORD_ARGON2ID);
        $pdo = DB::pdo();
        $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?")->execute([$hash, $userId]);

        Audit::log('password_change', 'users', $userId, null, ['forced_first_login' => true], $userId);

        Auth::reset();
        $_SESSION['flash_success'] = 'Your new password has been set successfully. Welcome to Msantha Poultry!';
        return Response::redirect(url('/'));
    }

    public function changePassword(Request $request): Response
    {
        Auth::requireRole(['owner', 'manager', 'worker', 'accountant', 'viewer']);

        $user = Auth::user();
        $userId = (int) $user['id'];

        $currentPass = (string) $request->input('current_password');
        $newPass = (string) $request->input('new_password');
        $confirm = (string) $request->input('confirm_password');

        if (!password_verify($currentPass, (string) $user['password_hash'])) {
            $_SESSION['flash_error'] = 'Current password is incorrect.';
            return Response::redirect(url('/change-password'));
        }

        if (strlen($newPass) < 8) {
            $_SESSION['flash_error'] = 'New password must be at least 8 characters long.';
            return Response::redirect(url('/change-password'));
        }

        if ($newPass !== $confirm) {
            $_SESSION['flash_error'] = 'New password and confirmation do not match.';
            return Response::redirect(url('/change-password'));
        }

        $hash = password_hash($newPass, PASSWORD_ARGON2ID);
        DB::pdo()->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $userId]);

        Audit::log('update', 'users', $userId, null, ['action' => 'password_change'], $userId);

        $_SESSION['flash_success'] = 'Your password has been changed successfully.';
        return Response::redirect(url('/change-password'));
    }
}

