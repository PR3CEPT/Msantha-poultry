<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1b6d39">
    <title><?= e($pageTitle ?? 'Verify Code') ?> — Msantha Investments (MIGS)</title>
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
        .code-input {
            letter-spacing: 0.35em;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
        }
    </style>
</head>
<body class="p-3">

    <div class="card border-0 shadow-lg rounded-4 overflow-hidden w-100 bg-white" style="max-width: 460px;">
        <div class="p-4 text-white text-center" style="background: linear-gradient(135deg, #14532d 0%, #1b6d39 100%);">
            <img src="<?= asset('assets/img/logo.png') ?>" alt="MIGS Logo" class="mb-2" style="height: 52px; width: auto; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));">
            <h4 class="fw-bold mb-1 text-white">Enter SMS Security Code</h4>
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
                    <div class="step-circle bg-success text-white">2</div>
                    <small class="fw-bold text-success mt-1" style="font-size: 0.72rem;">SMS Code</small>
                </div>
                <div class="flex-grow-1 border-top border-2 mx-2 mb-3"></div>
                <div class="d-flex flex-column align-items-center">
                    <div class="step-circle bg-light text-muted border">3</div>
                    <small class="text-muted mt-1" style="font-size: 0.72rem;">New Pass</small>
                </div>
            </div>

            <p class="text-muted small text-center mb-4">
                We sent a 6-digit code to your phone. It is valid for 10 minutes (maximum 3 attempts).
            </p>

            <?php if (!empty($flashSuccess)): ?>
                <div class="alert alert-success alert-dismissible fade show small rounded-3" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= e($flashSuccess) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($flashError)): ?>
                <div class="alert alert-danger alert-dismissible fade show small rounded-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($flashError) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/verify-reset-code') ?>">
                <?= csrf_field() ?>

                <div class="mb-4">
                    <label for="code" class="form-label fw-semibold text-dark small text-center d-block">Enter 6-Digit Code</label>
                    <input type="text" name="code" id="code" class="form-control text-center fs-3 code-input py-2" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="------" required autofocus>
                </div>

                <button type="submit" class="btn btn-success w-100 py-2 fs-6 fw-bold shadow-sm rounded-3 mb-3">
                    <i class="bi bi-shield-check me-2"></i>Verify & Continue
                </button>

                <div class="text-center">
                    <a href="<?= url('/forgot-password') ?>" class="text-decoration-none small text-muted">
                        <i class="bi bi-arrow-repeat me-1"></i>Didn't receive SMS? Try again
                    </a>
                </div>
            </form>
        </div>

        <div class="card-footer bg-light border-top text-center py-3 text-muted small">
            MIGS Emergency Support: <a href="tel:+265999899620" class="text-success fw-bold text-decoration-none">+265 9998 996 20</a>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
