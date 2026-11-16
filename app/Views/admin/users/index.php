<?php declare(strict_types=1); ?>
<div class="row g-3">
    <!-- Header -->
    <div class="col-12 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
        <div>
            <h2 class="h4 fw-bold mb-0">User Accounts</h2>
            <small class="text-muted">Manage farm staff roles, access permissions, and active device sessions</small>
        </div>
        <div>
            <a href="<?= url('/admin/users/create') ?>" class="btn btn-primary fw-bold shadow-sm">
                <i class="bi bi-person-plus me-1"></i>Add New User
            </a>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="col-12">
        <div class="card border-0 shadow-sm p-3">
            <form method="GET" action="<?= url('/admin/users') ?>" class="row g-2 align-items-center">
                <div class="col-12 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, username, phone..." value="<?= e($search ?? '') ?>">
                    </div>
                </div>
                <div class="col-8 col-md-4">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= e($r['code']) ?>" <?= ($roleFilter ?? '') === $r['code'] ? 'selected' : '' ?>>
                                <?= e($r['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-4 col-md-2 d-grid">
                    <button type="submit" class="btn btn-secondary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Users List: Desktop Table (d-none d-lg-block) -->
    <div class="col-12 d-none d-lg-block">
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Full Name</th>
                            <th scope="col">Username</th>
                            <th scope="col">Role</th>
                            <th scope="col">Session</th>
                            <th scope="col">Status</th>
                            <th scope="col">Password</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-people display-6 d-block mb-2 text-muted"></i>
                                    No users found matching your search.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="fw-semibold text-dark">
                                        <div><?= e($u['full_name']) ?></div>
                                        <div class="small text-muted font-monospace" style="font-size: 0.72rem;">
                                            <?php if (!empty($u['email'])): ?>
                                                <span class="me-2"><i class="bi bi-envelope text-primary me-1"></i><?= e($u['email']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($u['phone'])): ?>
                                                <span><i class="bi bi-telephone text-success me-1"></i><?= e($u['phone']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><code><?= e($u['username']) ?></code></td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= e($u['role_name']) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $hasActiveSession = !empty($u['active_session_time']) && (strtotime($u['active_session_time']) >= (time() - 900));
                                        ?>
                                        <?php if ($hasActiveSession): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>Active (<?= date('H:i', strtotime($u['active_session_time'])) ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Offline</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int) $u['is_active'] === 1): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int) $u['must_change_password'] === 1): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Must Change</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($hasActiveSession && $u['id'] != \App\Core\Auth::id()): ?>
                                            <form method="POST" action="<?= url("/admin/users/{$u['id']}/force-logout") ?>" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-warning me-1" data-confirm="Terminate active session for this user?" title="Force Logout">
                                                    <i class="bi bi-box-arrow-right"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="<?= url("/admin/users/{$u['id']}/edit") ?>" class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($u['id'] != \App\Core\Auth::id() && $u['username'] !== 'ik.chipeta'): ?>
                                            <form method="POST" action="<?= url("/admin/users/{$u['id']}/toggle-active") ?>" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm <?= (int) $u['is_active'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>" data-confirm="Are you sure you want to change this user's active status?">
                                                    <?= (int) $u['is_active'] === 1 ? '<i class="bi bi-person-x"></i>' : '<i class="bi bi-person-check"></i>' ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Users List: Mobile Cards (d-lg-none) -->
    <div class="col-12 d-lg-none">
        <div class="d-flex flex-column gap-2">
            <?php if (empty($users)): ?>
                <div class="card border-0 shadow-sm p-4 text-center text-muted">
                    No users found matching your search.
                </div>
            <?php else: ?>
                <?php foreach ($users as $u): ?>
                    <?php $hasActiveSession = !empty($u['active_session_time']) && (strtotime($u['active_session_time']) >= (time() - 900)); ?>
                    <div class="card border-0 shadow-sm p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="h6 fw-bold mb-0 text-dark"><?= e($u['full_name']) ?></h5>
                                <small class="text-muted">@<?= e($u['username']) ?></small>
                            </div>
                            <div class="d-flex gap-1">
                                <?php if ($hasActiveSession): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Online</span>
                                <?php endif; ?>
                                <span class="badge <?= (int) $u['is_active'] === 1 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?>">
                                    <?= (int) $u['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                                </span>
                            </div>
                        </div>
                        <div class="small text-muted mb-3">
                            <div><i class="bi bi-shield me-1"></i>Role: <strong><?= e($u['role_name']) ?></strong></div>
                            <?php if (!empty($u['email'])): ?>
                                <div><i class="bi bi-envelope me-1 text-primary"></i><?= e($u['email']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($u['phone'])): ?>
                                <div><i class="bi bi-telephone me-1 text-success"></i><?= e($u['phone']) ?></div>
                            <?php endif; ?>
                            <?php if ((int)$u['must_change_password'] === 1): ?>
                                <div><i class="bi bi-key me-1 text-warning"></i>Must change password at next login</div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?= url("/admin/users/{$u['id']}/edit") ?>" class="btn btn-sm btn-outline-secondary flex-fill">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                            <?php if ($hasActiveSession && $u['id'] != \App\Core\Auth::id()): ?>
                                <form method="POST" action="<?= url("/admin/users/{$u['id']}/force-logout") ?>" class="flex-fill">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm w-100 btn-outline-warning" data-confirm="Terminate active session?">
                                        <i class="bi bi-box-arrow-right me-1"></i>Force Logout
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
