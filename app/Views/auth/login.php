<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1b6d39">
    <title><?= e($pageTitle ?? 'Sign In') ?> — Msantha Investments (MIGS)</title>
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
        .login-card-wrapper {
            max-width: 960px;
            width: 100%;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 40px -15px rgba(0,0,0,0.12), 0 0 0 1px rgba(0,0,0,0.04);
        }
        .login-brand-panel {
            background: linear-gradient(135deg, #0f3d1e 0%, #1b6d39 60%, #15803d 100%);
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }
        .login-brand-panel::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 80% 20%, rgba(245, 158, 11, 0.2) 0%, transparent 60%);
            pointer-events: none;
        }
        .role-pill-btn {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #334155;
        }
        .role-pill-btn:hover {
            background-color: #f0fdf4;
            border-color: #16a34a;
            color: #16a34a;
        }
    </style>
</head>
<body class="p-3 p-md-4">

    <div class="login-card-wrapper bg-white">
        <div class="row g-0">
            <!-- Left Branding Side (Desktop) -->
            <div class="col-12 col-lg-5 login-brand-panel p-4 p-md-5 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <img src="<?= asset('assets/img/logo.png') ?>" alt="MIGS Logo" style="height: 64px; width: auto; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));">
                        <div>
                            <span class="badge bg-warning text-dark fw-bold px-2 py-1 mb-1">M I G S &bull; Est. 2006</span>
                            <h5 class="fw-bold text-white mb-0 lh-sm">Msantha Investments</h5>
                            <small class="text-white-50" style="font-size: 0.78rem;">And General Suppliers</small>
                        </div>
                    </div>

                    <p class="text-white-50 small mb-4" style="line-height: 1.6;">
                        "This is to introduce to you Msantha Investments and General Suppliers. We are located in Liwonde, Mawira area by Chipamba road, Machinga district. We specialize in livestock and poultry production and veterinary services."
                    </p>

                    <div class="p-3 rounded-3 mb-4" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(4px); border-left: 4px solid #f59e0b;">
                        <p class="text-white fw-semibold small mb-1 fst-italic">
                            "WE ARE BEST AT WHAT WE DO, TRY US AND YOU WON'T REGRET"
                        </p>
                        <small class="text-warning">&mdash; We Supply and Ear Tag for You</small>
                    </div>
                </div>

                <div class="pt-3 border-top border-white-50 text-white-50 small">
                    <div><i class="bi bi-geo-alt me-1 text-warning"></i>Liwonde, Mawira &bull; P.O. Box 250</div>
                    <div><i class="bi bi-telephone me-1 text-warning"></i>+265 8888 996 20 / 9998 996 20</div>
                </div>
            </div>

            <!-- Right Form Side -->
            <div class="col-12 col-lg-7 p-4 p-md-5 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="h3 fw-bold text-dark mb-1">Portal Sign In</h2>
                            <p class="text-muted small mb-0">Enter your farm credentials to access operations</p>
                        </div>
                        <a href="<?= url('/') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                            <i class="bi bi-house me-1"></i>Website
                        </a>
                    </div>

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

                    <form method="POST" action="<?= url('/login') ?>" id="loginForm">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold text-dark small">Username or Phone</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" id="username" class="form-control border-start-0" placeholder="e.g. admin or 0999899620" required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="form-label fw-semibold text-dark small mb-0">Password</label>
                                <a href="<?= url('/forgot-password') ?>" class="small text-decoration-none text-success fw-semibold">Forgot Password?</a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control border-start-0 border-end-0" placeholder="••••••••" required>
                                <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="toggleLoginPassword(this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember" checked>
                                <label class="form-check-label small text-muted" for="remember">Keep me signed in on this device</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-2 fs-6 fw-bold shadow-sm rounded-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In to Dashboard
                        </button>
                    </form>
                </div>

                <div class="text-center mt-5 text-muted small">
                    &copy; <?= date('Y') ?> Msantha Investments & General Suppliers &bull; Liwonde, Machinga
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleLoginPassword(btn) {
        const pass = document.getElementById('password');
        const icon = btn.querySelector('i');
        if (pass.type === 'password') {
            pass.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            pass.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
    </script>
</body>
</html>
