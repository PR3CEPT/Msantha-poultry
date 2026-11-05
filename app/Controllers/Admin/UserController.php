<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Phone;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;
use RuntimeException;

class UserController
{
    private function checkAccess(): void
    {
        if (!Auth::canManageUsers()) {
            $resp = View::render('errors/403', [
                'pageTitle' => '403 Forbidden',
                'message' => 'Access Denied: Only the MD (owner) or Isaac (users.change_roles) can manage user accounts.',
            ]);
            $resp->setStatusCode(403)->send();
            exit(0);
        }
    }

    public function index(Request $request): Response
    {
        $this->checkAccess();
        Audit::logView('admin_users');

        $pdo = DB::pdo();
        $search = trim((string) $request->input('search', ''));
        $roleFilter = trim((string) $request->input('role', ''));

        $sql = "
            SELECT u.*, r.code AS role_code, r.name AS role_name,
                   us.last_activity_at AS active_session_time
            FROM users u
            JOIN roles r ON r.id = u.role_id
            LEFT JOIN user_sessions us ON us.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (u.full_name LIKE :s OR u.username LIKE :s OR u.phone LIKE :s OR u.email LIKE :s)";
            $params['s'] = "%{$search}%";
        }

        if ($roleFilter !== '') {
            $sql .= " AND r.code = :r";
            $params['r'] = $roleFilter;
        }

        $sql .= " ORDER BY u.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        $rolesStmt = $pdo->query("SELECT * FROM roles ORDER BY id ASC");
        $roles = $rolesStmt->fetchAll();

        return View::render('admin/users/index', [
            'pageTitle' => 'User Management',
            'users' => $users,
            'roles' => $roles,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'activeNav' => 'admin_users',
        ]);
    }

    public function create(Request $request): Response
    {
        $this->checkAccess();

        $roles = DB::pdo()->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

        return View::render('admin/users/create', [
            'pageTitle' => 'Add New User',
            'roles' => $roles,
            'activeNav' => 'admin_users',
        ]);
    }

    public function store(Request $request): Response
    {
        $this->checkAccess();

        $validator = Validator::make($request->all(), [
            'full_name' => 'required',
            'username' => 'required',
            'role_id' => 'required|integer',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = $validator->firstError();
            return Response::redirect(url('/admin/users/create'));
        }

        $pdo = DB::pdo();
        $targetRole = (int) $request->input('role_id');

        // Only owner can create another owner
        $ownerRoleId = (int) $pdo->query("SELECT id FROM roles WHERE code = 'owner' LIMIT 1")->fetchColumn();
        if ($targetRole === $ownerRoleId && Auth::role() !== 'owner') {
            $_SESSION['flash_error'] = "Only the MD (owner) can assign the owner role.";
            return Response::redirect(url('/admin/users/create'));
        }

        $username = trim((string) $request->input('username'));
        $phone = Phone::normalize((string) $request->input('phone'));
        $email = trim((string) $request->input('email'));
        if ($email === '') $email = null;

        // Check unique username
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $checkStmt->execute([$username]);
        if ($checkStmt->fetch()) {
            $_SESSION['flash_error'] = "Username '{$username}' is already taken.";
            return Response::redirect(url('/admin/users/create'));
        }

        $hash = password_hash((string) $request->input('password'), PASSWORD_ARGON2ID);

        $stmt = $pdo->prepare("
            INSERT INTO users (role_id, full_name, phone, email, username, password_hash, is_active, must_change_password)
            VALUES (?, ?, ?, ?, ?, ?, 1, 1)
        ");
        $stmt->execute([
            $targetRole,
            (string) $request->input('full_name'),
            $phone,
            $email,
            $username,
            $hash,
        ]);
        $newId = (int) $pdo->lastInsertId();

        Audit::log('create', 'users', $newId, null, [
            'username' => $username,
            'full_name' => $request->input('full_name'),
            'role_id' => $targetRole,
        ]);

        $_SESSION['flash_success'] = "User '{$username}' created successfully.";
        return Response::redirect(url('/admin/users'));
    }

    public function edit(Request $request, string $id): Response
    {
        $this->checkAccess();

        $pdo = DB::pdo();
        $stmt = $pdo->prepare("SELECT u.*, r.code AS role_code FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? LIMIT 1");
        $stmt->execute([(int) $id]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['flash_error'] = 'User not found.';
            return Response::redirect(url('/admin/users'));
        }

        // Only owner can touch MD account
        if ($user['username'] === 'ik.chipeta' && Auth::role() !== 'owner') {
            $_SESSION['flash_error'] = "Only the MD (owner) can edit the MD account.";
            return Response::redirect(url('/admin/users'));
        }

        $roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

        return View::render('admin/users/edit', [
            'pageTitle' => 'Edit User',
            'user' => $user,
            'roles' => $roles,
            'activeNav' => 'admin_users',
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $this->checkAccess();

        $userId = (int) $id;
        $pdo = DB::pdo();

        $stmtOld = $pdo->prepare("SELECT u.*, r.code AS role_code FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? LIMIT 1");
        $stmtOld->execute([$userId]);
        $oldUser = $stmtOld->fetch();

        if (!$oldUser) {
            $_SESSION['flash_error'] = 'User not found.';
            return Response::redirect(url('/admin/users'));
        }

        // Rule A2: Only owner can touch MD account
        if ($oldUser['username'] === 'ik.chipeta' && Auth::role() !== 'owner') {
            $_SESSION['flash_error'] = "Access Denied: Only the MD can edit the MD account.";
            return Response::redirect(url('/admin/users'));
        }

        $newRoleId = (int) $request->input('role_id');
        $ownerRoleId = (int) $pdo->query("SELECT id FROM roles WHERE code = 'owner' LIMIT 1")->fetchColumn();

        // Rule A2: Isaac cannot make anyone an owner
        if ($newRoleId === $ownerRoleId && Auth::role() !== 'owner') {
            $_SESSION['flash_error'] = "Access Denied: Only the owner can assign the owner role.";
            return Response::redirect(url("/admin/users/{$userId}/edit"));
        }

        // Rule A2: Nobody can change their own role
        if ($userId === Auth::id() && $newRoleId !== (int) $oldUser['role_id']) {
            $_SESSION['flash_error'] = "You cannot change your own role.";
            return Response::redirect(url("/admin/users/{$userId}/edit"));
        }

        $validator = Validator::make($request->all(), [
            'full_name' => 'required',
            'role_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = $validator->firstError();
            return Response::redirect(url("/admin/users/{$userId}/edit"));
        }

        $phone = Phone::normalize((string) $request->input('phone'));
        $email = trim((string) $request->input('email'));
        if ($email === '') $email = null;

        $newPassword = (string) $request->input('password');

        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                $_SESSION['flash_error'] = 'Password must be at least 8 characters long.';
                return Response::redirect(url("/admin/users/{$userId}/edit"));
            }
            $hash = password_hash($newPassword, PASSWORD_ARGON2ID);
            $stmt = $pdo->prepare("
                UPDATE users SET role_id = ?, full_name = ?, phone = ?, email = ?, password_hash = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $newRoleId,
                (string) $request->input('full_name'),
                $phone,
                $email,
                $hash,
                $userId,
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users SET role_id = ?, full_name = ?, phone = ?, email = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $newRoleId,
                (string) $request->input('full_name'),
                $phone,
                $email,
                $userId,
            ]);
        }

        // Record role change in audit log
        if ($newRoleId !== (int) $oldUser['role_id']) {
            Audit::log('role_change', 'users', $userId, [
                'role_id' => $oldUser['role_id'],
                'role_code' => $oldUser['role_code'],
            ], [
                'role_id' => $newRoleId,
            ]);
        }

        Audit::log('update', 'users', $userId, [
            'full_name' => $oldUser['full_name'],
            'phone' => $oldUser['phone'],
        ], [
            'full_name' => $request->input('full_name'),
            'phone' => $phone,
        ]);

        $_SESSION['flash_success'] = "User updated successfully.";
        return Response::redirect(url('/admin/users'));
    }

    public function toggleActive(Request $request, string $id): Response
    {
        $this->checkAccess();

        $userId = (int) $id;
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("SELECT is_active, username, role_id FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['flash_error'] = "User not found.";
            return Response::redirect(url('/admin/users'));
        }

        // Rule A2: Nobody can deactivate themselves
        if ($userId === Auth::id()) {
            $_SESSION['flash_error'] = "You cannot deactivate your own account.";
            return Response::redirect(url('/admin/users'));
        }

        // Rule A2: Isaac cannot touch MD account
        if ($user['username'] === 'ik.chipeta' && Auth::role() !== 'owner') {
            $_SESSION['flash_error'] = "Only the MD can deactivate the MD account.";
            return Response::redirect(url('/admin/users'));
        }

        $newActive = ((int) $user['is_active']) === 1 ? 0 : 1;
        $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$newActive, $userId]);

        Audit::log($newActive === 1 ? 'user_activate' : 'user_deactivate', 'users', $userId, [
            'is_active' => $user['is_active'],
        ], [
            'is_active' => $newActive,
        ]);

        $statusLabel = $newActive === 1 ? 'activated' : 'deactivated';
        $_SESSION['flash_success'] = "User '{$user['username']}' {$statusLabel}.";
        return Response::redirect(url('/admin/users'));
    }

    public function forceLogout(Request $request, string $id): Response
    {
        $this->checkAccess();

        $userId = (int) $id;
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['flash_error'] = "User not found.";
            return Response::redirect(url('/admin/users'));
        }

        // Rule A2: Isaac cannot force logout the MD
        if ($user['username'] === 'ik.chipeta' && Auth::role() !== 'owner') {
            $_SESSION['flash_error'] = "Access Denied: Only the MD can force logout the MD account.";
            return Response::redirect(url('/admin/users'));
        }

        Auth::forceLogoutUser($userId);

        $_SESSION['flash_success'] = "Active session for '{$user['username']}' was terminated.";
        return Response::redirect(url('/admin/users'));
    }
}
