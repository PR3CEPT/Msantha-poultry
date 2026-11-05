<?php declare(strict_types=1); ?>
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm p-4">
            <h3 class="h5 fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square text-success"></i>Edit User: <?= e($user['username']) ?>
            </h3>

            <form method="POST" action="<?= url("/admin/users/{$user['id']}") ?>">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="full_name" class="form-label fw-semibold small text-muted">Full Name</label>
                    <input type="text" name="full_name" id="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold small text-muted">Username</label>
                    <input type="text" id="username" class="form-control bg-light" value="<?= e($user['username']) ?>" disabled>
                    <small class="text-muted">Username cannot be changed</small>
                </div>

                <div class="mb-3">
                    <label for="role_id" class="form-label fw-semibold small text-muted">Role</label>
                    <select name="role_id" id="role_id" class="form-select" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= e($r['id']) ?>" <?= (int) $user['role_id'] === (int) $r['id'] ? 'selected' : '' ?>>
                                <?= e($r['name']) ?> (<?= e($r['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label fw-semibold small text-muted">Phone Number</label>
                    <input type="tel" name="phone" id="phone" class="form-control" inputmode="tel" value="<?= e($user['phone'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small text-muted">Email</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= e($user['email'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold small text-muted">Reset Password (leave empty to keep current)</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" minlength="8">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="bi bi-check-lg me-1"></i>Save Changes
                    </button>
                    <a href="<?= url('/admin/users') ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

