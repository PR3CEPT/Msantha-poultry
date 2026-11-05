<?php declare(strict_types=1); ?>
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm p-4">
            <h3 class="h5 fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-person-plus text-success"></i>Add New User
            </h3>
            <p class="small text-muted mb-4">Create a new system user account with assigned role and login credentials.</p>

            <form method="POST" action="<?= url('/admin/users') ?>">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="full_name" class="form-label fw-semibold small text-muted">Full Name</label>
                    <input type="text" name="full_name" id="full_name" class="form-control" placeholder="e.g. Kondwani Phiri" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold small text-muted">Username (login handle)</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="e.g. kphiri" required>
                </div>

                <div class="mb-3">
                    <label for="role_id" class="form-label fw-semibold small text-muted">Role</label>
                    <select name="role_id" id="role_id" class="form-select" required>
                        <option value="">Select Role...</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= e($r['id']) ?>"><?= e($r['name']) ?> (<?= e($r['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label fw-semibold small text-muted">Phone Number (E.164 / Malawi local)</label>
                    <input type="tel" name="phone" id="phone" class="form-control" inputmode="tel" placeholder="0999899620 or +265999899620">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small text-muted">Email (optional)</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="staff@msantha.mw">
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold small text-muted">Temporary Password (min 8 characters)</label>
                    <input type="password" name="password" id="password" class="form-control" minlength="8" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="bi bi-check-lg me-1"></i>Create User
                    </button>
                    <a href="<?= url('/admin/users') ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

