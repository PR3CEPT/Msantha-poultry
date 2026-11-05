<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1b6d39">
    <title><?= e($pageTitle ?? 'Set New Password') ?> — Msantha Investments (MIGS)</title>
    <link rel="icon" type="image/png" href="<?= asset('assets/img/logo.png') ?>">
    <link rel="manifest" href="<?= asset('manifest.webmanifest') ?>">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 & Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
    <style>
        body {
            background-color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
        }
    </style>
</head>
<body class="p-3">

    <div class="card border-0 shadow-lg rounded-4 overflow-hidden w-100 bg-white" style="max-width: 460px;">
        <div class="p-4 text-white text-center" style="background: linear-gradient(135deg, #14532d 0%, #1b6d39 100%);">
            <img src="<?= asset('assets/img/logo.png') ?>" alt="MIGS Logo" class="mb-2" style="height: 52px; width: auto; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));">
            <h4 class="fw-bold mb-1 text-white">Create New Password</h4>
            <small class="text-white-50">Msantha Investments & General Suppliers</small>
        </div>

        <div class="card-body p-4 p-md-5">
            <!-- Progress Stepper -->
            <div class="d-flex align-items-center justify-content-between mb-4 px-3">
                <div class="d-flex flex-column align-items-center">
                    <div class="step-circle bg-success text-white"><i class="bi bi-check"></i></div>
                    <small class="fw-bold text-success mt-1" style="font-size: 0.72rem;">Phone</small>
                </div>
                <div class="flex-grow-1 border-top border-2 border-success mx-2 mb-3"></div>
                <div class="d-flex flex-column align-items-center">
                    <div class="step-circle bg-success text-white"><i class="bi bi-check"></i></div>
                    <small class="fw-bold text-success mt-1" style="font-size: 0.72rem;">SMS Code</small>
                </div>
                <div class="flex-grow-1 border-top border-2 border-success mx-2 mb-3"></div>
                <div class="d-flex flex-column align-items-center">
                    <div class="step-circle bg-success text-white">3</div>
                    <small class="fw-bold text-success mt-1" style="font-size: 0.72rem;">New Pass</small>
                </div>
            </div>

            <p class="text-muted small text-center mb-4">
                Enter your new password. It must be at least 8 characters long and will be hashed with Argon2id.
            </p>

            <?php if (!empty($flashError)): ?>
                <div class="alert alert-danger alert-dismissible fade show small rounded-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($flashError) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/reset-password') ?>">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold text-dark small">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control border-start-0 border-end-0" placeholder="Minimum 8 characters" minlength="8" required autofocus>
                        <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="togglePass('password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label fw-semibold text-dark small">Confirm New Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control border-start-0 border-end-0" placeholder="Repeat new password" minlength="8" required>
                        <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="togglePass('password_confirmation', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100 py-2 fs-6 fw-bold shadow-sm rounded-3 mb-3">
                    <i class="bi bi-check2-circle me-2"></i>Save New Password & Sign In
                </button>
            </form>
        </div>

        <div class="card-footer bg-light border-top text-center py-3 text-muted small">
            Liwonde Station &bull; P.O. Box 250, Machinga District
        </div>
    </div>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePass(id, btn) {
        const input = document.getElementById(id);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
    </script>
</body>
</html>
