<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1b6d39">
    <title><?= e($pageTitle ?? 'Reset Password') ?> — Msantha Investments (MIGS)</title>
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
        <!-- Header Banner -->
        <div class="p-4 text-white text-center" style="background: linear-gradient(135deg, #14532d 0%, #1b6d39 100%);">
            <img src="<?= asset('assets/img/logo.png') ?>" alt="MIGS Logo" class="mb-2" style="height: 52px; width: auto; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));">
            <h4 class="fw-bold mb-1 text-white">Reset Account Password</h4>
            <small class="text-white-50">Msantha Investments and General Suppliers (MIGS)</small>
        </div>

        <div class="card-body p-4 p-md-5">
            <!-- Progress Stepper -->
            <div class="d-flex align-items-center justify-content-between mb-4 px-3">
                <div class="d-flex flex-column align-items-center">
                    <div class="step-circle bg-success text-white">1</div>
                    <small class="fw-bold text-success mt-1" style="font-size: 0.72rem;">Enter Phone</small>
                </div>
                <div class="flex-grow-1 border-top border-2 mx-2 mb-3"></div>
                <div class="d-flex flex-column align-items-center">
                    <div class="step-circle bg-light text-muted border">2</div>
                    <small class="text-muted mt-1" style="font-size: 0.72rem;">SMS Code</small>
                </div>
                <div class="flex-grow-1 border-top border-2 mx-2 mb-3"></div>
                <div class="d-flex flex-column align-items-center">
                    <div class="step-circle bg-light text-muted border">3</div>
                    <small class="text-muted mt-1" style="font-size: 0.72rem;">New Pass</small>
                </div>
            </div>

            <p class="text-muted small text-center mb-4">
                Enter your username (e.g. <code>ik.chipeta</code>) or registered phone number. You can create a new password directly or request an SMS security code.
            </p>

            <?php if (!empty($flashError)): ?>
                <div class="alert alert-danger alert-dismissible fade show small rounded-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($flashError) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/forgot-password') ?>">
                <?= csrf_field() ?>

                <div class="mb-4">
                    <label for="identifier" class="form-label fw-semibold text-dark small">Username or Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-person-badge"></i></span>
                        <input type="text" name="identifier" id="identifier" class="form-control" placeholder="e.g. ik.chipeta or 0999899620" required autofocus>
                    </div>
                    <div class="form-text small text-muted">Enter your account username to reset directly without needing a phone number.</div>
                </div>

                <div class="d-flex flex-column gap-2 mb-3">
                    <button type="submit" name="skip_phone" value="1" class="btn btn-success w-100 py-2.5 fs-6 fw-bold shadow-sm rounded-3">
                        <i class="bi bi-key-fill me-2"></i>Create New Password (No Phone Needed)
                    </button>
                    <button type="submit" name="skip_phone" value="0" class="btn btn-outline-secondary w-100 py-2 small fw-semibold rounded-3">
                        <i class="bi bi-chat-dots me-2"></i>Send 6-Digit SMS Verification Code
                    </button>
                </div>

                <div class="text-center">
                    <a href="<?= url('/login') ?>" class="text-decoration-none small text-muted">
                        <i class="bi bi-arrow-left me-1"></i>Back to Sign In
                    </a>
                </div>
            </form>
        </div>

        <div class="card-footer bg-light border-top text-center py-3 text-muted small">
            Liwonde Station &bull; Helpdesk: <a href="tel:+265999899620" class="text-success fw-bold text-decoration-none">+265 9998 996 20</a>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
