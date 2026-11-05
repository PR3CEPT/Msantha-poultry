<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1b6d39">
    <title>Set Your Password — Msantha Poultry</title>
    <link rel="icon" type="image/png" href="<?= asset('assets/img/logo.png') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-light p-3">
    <div class="card border-0 shadow-sm w-100" style="max-width: 440px; border-radius: 16px;">
        <div class="card-body p-4 p-sm-5">
            <div class="text-center mb-4">
                <i class="bi bi-shield-lock-fill text-success display-4"></i>
                <h1 class="h4 fw-bold text-dark mt-2 mb-1">Set Your Permanent Password</h1>
                <p class="text-muted small">This is your first login. To protect the farm system, you must choose your own secret password (minimum 10 characters) before continuing.</p>
            </div>

            <?php if (!empty($flashError)): ?>
                <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($flashError) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/force-password-change') ?>">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold small text-muted">New Secret Password (min 10 characters)</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Minimum 10 characters" minlength="10" required autofocus>
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label fw-semibold small text-muted">Confirm Secret Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Repeat your new password" minlength="10" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fs-6 fw-bold shadow-sm">
                    <i class="bi bi-check2-circle me-2"></i>Save Password & Continue
                </button>
            </form>
        </div>
    </div>
</body>
</html>

