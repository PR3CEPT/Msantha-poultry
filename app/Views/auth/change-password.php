<?php declare(strict_types=1); ?>
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm p-4">
            <h3 class="h5 fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock text-success"></i>Change Your Password
            </h3>
            <p class="small text-muted mb-4">Update your password securely. Minimum length is 8 characters.</p>

            <form method="POST" action="<?= url('/change-password') ?>">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="current_password" class="form-label fw-semibold small text-muted">Current Password</label>
                    <input type="password" name="current_password" id="current_password" class="form-control" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label fw-semibold small text-muted">New Password (min 8 chars)</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" minlength="8" required>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label fw-semibold small text-muted">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="8" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="bi bi-check-lg me-1"></i>Save New Password
                    </button>
                    <a href="<?= url('/') ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

