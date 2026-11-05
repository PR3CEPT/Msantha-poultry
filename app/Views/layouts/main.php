<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1b6d39">
    <title><?= e($pageTitle ?? 'Dashboard') ?> — Msantha Investments (MIGS)</title>
    <link rel="icon" type="image/png" href="<?= asset('assets/img/logo.png') ?>">
    <link rel="manifest" href="<?= asset('manifest.webmanifest') ?>">
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
    <style>
        .app-topbar {
            height: 68px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
        }
        .app-sidebar {
            width: 265px;
            background-color: #f8fafc;
            border-right: 1px solid #e2e8f0;
            position: sticky;
            top: 68px;
            height: calc(100vh - 68px);
            overflow-y: auto;
        }
        .sidebar-brand-card {
            background: linear-gradient(135deg, #0b2914 0%, #1b6d39 100%);
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 12px;
            color: #ffffff;
        }
        .sidebar-section-title {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            padding: 0.65rem 0.8rem 0.25rem;
            margin-top: 0.4rem;
        }
        .app-sidebar .nav-link {
            font-size: 0.86rem;
            font-weight: 500;
            border-radius: 10px;
            padding: 0.52rem 0.85rem;
            color: #334155;
            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            align-items: center;
            margin-bottom: 2px;
            border: 1px solid transparent;
        }
        .app-sidebar .nav-link:hover {
            background-color: #e2e8f0;
            color: #0f172a;
            transform: translateX(2px);
        }
        .app-sidebar .nav-link.active {
            background: linear-gradient(135deg, #14532d 0%, #1b6d39 100%) !important;
            color: #ffffff !important;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(27, 109, 57, 0.28);
            border-color: rgba(255, 255, 255, 0.15);
        }
        .app-sidebar .nav-link.active i {
            color: #ffffff !important;
        }
        .nav-icon-wrap {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            flex-shrink: 0;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: all 0.18s ease;
        }
        .app-sidebar .nav-link.active .nav-icon-wrap {
            background: rgba(255, 255, 255, 0.2);
            box-shadow: none;
        }
        .app-main-content {
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 68px);
            background: #f8fafc;
        }
        .content-body {
            flex: 1;
        }
        .app-footer {
            background-color: #0b2914;
            color: #ffffff;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .app-footer a {
            color: rgba(255, 255, 255, 0.65);
            transition: color 0.15s ease;
        }
        .app-footer a:hover {
            color: #ffffff;
        }
    </style>
</head>
<body>
    <?php
    $currentUser = \App\Core\Auth::user();
    $currentRole = \App\Core\Auth::role();
    $systemAlerts = $currentUser ? \App\Services\NotificationService::getActiveAlerts() : [];
    $urgentAlertsCount = count($systemAlerts);
    ?>

    <!-- Top Bar -->
    <header class="app-topbar d-flex align-items-center justify-content-between px-3 px-md-4">
        <div class="d-flex align-items-center gap-3">
            <!-- Mobile Sidebar Toggle -->
            <button class="btn btn-sm btn-light border d-lg-none me-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                <i class="bi bi-list fs-5"></i>
            </button>

            <a href="<?= url('/') ?>" class="d-flex align-items-center text-decoration-none">
                <img src="<?= asset('assets/img/logo.png') ?>" alt="Msantha Investments Logo" class="app-logo me-2" style="height: 42px; width: auto;">
            </a>
            <div class="d-flex flex-column">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold fs-6 text-dark lh-sm">Msantha Investments</span>
                    <span class="badge bg-warning text-dark fw-bold d-none d-sm-inline" style="font-size: 0.65rem;">MIGS</span>
                </div>
                <small class="text-muted d-none d-sm-inline" style="font-size: 0.72rem;">
                    <i class="bi bi-geo-alt text-danger me-1"></i>Mawira Area by Chipamba Road, Liwonde, Machinga
                </small>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- Offline & Sync Status -->
            <span id="offlineBadge" class="badge bg-warning text-dark d-none">
                <i class="bi bi-wifi-off me-1"></i>Offline
            </span>
            <button id="syncBadge" type="button" class="btn btn-sm btn-outline-success d-none py-1 px-2 fw-semibold" style="font-size: 0.75rem;" title="Offline entries pending. Click to sync.">
                <i class="bi bi-arrow-repeat me-1"></i>Sync (<span id="syncCount">0</span>)
            </button>

            <!-- Station Badge -->
            <span class="badge bg-light text-dark border d-none d-md-inline-flex align-items-center gap-1 py-1 px-2.5 rounded-pill" style="font-size: 0.75rem;">
                <i class="bi bi-geo-alt-fill text-danger"></i>Liwonde Station &bull; MWK
            </span>

            <?php if ($currentUser): ?>
                <!-- In-System Notification Center -->
                <div class="dropdown me-1">
                    <button class="btn btn-sm btn-light border rounded-pill position-relative d-flex align-items-center justify-content-center" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 36px; height: 36px;" title="Notifications">
                        <i class="bi bi-bell-fill <?= $urgentAlertsCount > 0 ? 'text-danger' : 'text-secondary' ?>"></i>
                        <?php if ($urgentAlertsCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" style="font-size: 0.62rem; padding: 0.22em 0.42em;">
                                <?= $urgentAlertsCount ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 p-0" style="min-width: 320px; max-width: 360px; z-index: 1050;">
                        <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between rounded-top-4">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-bell-fill text-success"></i>
                                <strong class="text-dark small">Farm Operational Alerts</strong>
                            </div>
                            <span class="badge bg-success text-white" style="font-size: 0.68rem;"><?= $urgentAlertsCount ?> Active</span>
                        </div>
                        <div class="list-group list-group-flush" style="max-height: 320px; overflow-y: auto;">
                            <?php if (empty($systemAlerts)): ?>
                                <div class="p-4 text-center text-muted small">
                                    <i class="bi bi-check-circle-fill text-success fs-4 d-block mb-1"></i>
                                    All systems normal. No active farm alerts.
                                </div>
                            <?php else: ?>
                                <?php foreach ($systemAlerts as $alert): ?>
                                    <a href="<?= e($alert['link']) ?>" class="list-group-item list-group-item-action p-2.5 d-flex gap-2 align-items-start border-bottom">
                                        <span class="badge bg-<?= $alert['type'] ?>-subtle text-<?= $alert['type'] ?> border border-<?= $alert['type'] ?>-subtle mt-1 rounded-circle p-1">
                                            <i class="bi <?= $alert['type'] === 'danger' ? 'bi-exclamation-circle-fill' : 'bi-info-circle-fill' ?>" style="font-size: 0.75rem;"></i>
                                        </span>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="small text-dark"><?= e($alert['title']) ?></strong>
                                                <span class="text-muted font-monospace" style="font-size: 0.65rem;"><?= e($alert['time']) ?></span>
                                            </div>
                                            <p class="small text-muted mb-0 lh-sm mt-0.5" style="font-size: 0.78rem;"><?= e($alert['message']) ?></p>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="p-2 text-center bg-light border-top rounded-bottom-4">
                            <a href="<?= url('/reports') ?>" class="text-success small fw-semibold text-decoration-none">Operational Reports &rarr;</a>
                        </div>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2 py-1 px-2 rounded-pill shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle bg-success text-white fw-bold d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.78rem;">
                            <?= strtoupper(substr((string) ($currentUser['full_name'] ?? 'U'), 0, 1)) ?>
                        </div>
                        <span class="fw-semibold d-none d-sm-inline text-dark small"><?= e($currentUser['full_name']) ?></span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle d-none d-md-inline" style="font-size: 0.68rem;">
                            <?= strtoupper(e($currentRole ?? 'USER')) ?>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2" style="min-width: 240px;">
                        <li class="px-3 py-2 bg-light border-bottom">
                            <strong class="d-block text-dark small"><?= e($currentUser['full_name']) ?></strong>
                            <small class="text-muted d-block font-monospace">@<?= e($currentUser['username']) ?></small>
                            <span class="badge bg-warning text-dark mt-1" style="font-size: 0.65rem;">Role: <?= strtoupper(e($currentRole ?? 'USER')) ?></span>
                        </li>
                        <li><a class="dropdown-item py-2" href="<?= url('/profile') ?>"><i class="bi bi-person-circle text-success me-2"></i>My Profile & Security</a></li>
                        <li><a class="dropdown-item py-2" href="<?= url('/change-password') ?>"><i class="bi bi-key text-warning me-2"></i>Change Password</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <?php if ($currentRole === 'owner'): ?>
                            <li><a class="dropdown-item py-2" href="<?= url('/admin/users') ?>"><i class="bi bi-people text-primary me-2"></i>Staff Accounts</a></li>
                            <li><a class="dropdown-item py-2" href="<?= url('/admin/backups') ?>"><i class="bi bi-database-check text-info me-2"></i>Database Backups</a></li>
                        <?php endif; ?>
                        <?php if (in_array($currentRole, ['owner', 'manager'], true)): ?>
                            <li><a class="dropdown-item py-2" href="<?= url('/admin/import') ?>"><i class="bi bi-box-arrow-in-down text-secondary me-2"></i>Data Import Center</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item py-2" href="<?= url('/landing') ?>" target="_blank"><i class="bi bi-globe text-primary me-2"></i>Public Website</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <form method="POST" action="<?= url('/logout') ?>" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item py-2 text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="<?= url('/login') ?>" class="btn btn-sm btn-success fw-bold px-3">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Mobile Offcanvas Sidebar Drawer -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel" style="width: 280px;">
        <div class="offcanvas-header bg-light border-bottom py-3">
            <div class="d-flex align-items-center gap-2">
                <img src="<?= asset('assets/img/logo.png') ?>" alt="MIGS" style="height: 36px; width: auto;">
                <div>
                    <h6 class="offcanvas-title fw-bold text-dark mb-0" id="mobileSidebarLabel">Msantha Farm</h6>
                    <small class="text-muted" style="font-size: 0.7rem;">MIGS Liwonde Station</small>
                </div>
            </div>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-3">
            <?php include __DIR__ . '/_sidebar_nav.php'; ?>
        </div>
    </div>

    <!-- PWA Install Banner Hint -->
    <div id="pwaInstallBanner" class="alert alert-success border-0 rounded-0 shadow-sm mb-0 d-none py-2 px-3 align-items-center justify-content-between" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-phone text-success fs-5"></i>
            <span class="small fw-semibold">Install Msantha Poultry App on your phone for rapid offline entry</span>
        </div>
        <div class="d-flex gap-2">
            <button id="pwaInstallBtn" type="button" class="btn btn-sm btn-success py-1 px-3 fw-bold">Install</button>
            <button id="pwaDismissBtn" type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" aria-label="Close">&times;</button>
        </div>
    </div>

    <!-- Layout Wrapper -->
    <div class="app-content-wrapper">
        <!-- Desktop Sidebar (>= 992px) -->
        <aside class="app-sidebar p-3 d-none d-lg-block">
            <?php include __DIR__ . '/_sidebar_nav.php'; ?>
        </aside>

        <!-- Main Body Content Area -->
        <main class="app-main-content">
            <div class="content-body">
                <!-- Alert Area -->
                <?php if (!empty($_SESSION['flash_success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show m-3 m-md-4 rounded-3 shadow-sm" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i><?= e($_SESSION['flash_success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['flash_success']); ?>
                <?php endif; ?>
                <?php if (!empty($_SESSION['flash_error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show m-3 m-md-4 rounded-3 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($_SESSION['flash_error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['flash_error']); ?>
                <?php endif; ?>

                <?= $content ?? '' ?>
            </div>

            <!-- Global MIGS Professional Footer Across All System Pages -->
            <footer class="app-footer pt-5 pb-4 mt-auto">
                <div class="container-fluid px-3 px-md-4">
                    <div class="row g-4 mb-4">
                        <div class="col-12 col-md-4">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <img src="<?= asset('assets/img/logo.png') ?>" alt="MIGS Logo" style="height: 44px; width: auto; filter: brightness(1.2);">
                                <div>
                                    <h6 class="fw-bold text-white mb-0">Msantha Investments</h6>
                                    <small class="text-white-50">And General Suppliers (MIGS)</small>
                                </div>
                            </div>
                            <p class="text-white-50 small mb-3">
                                Established in 2006. Dedicated to quality livestock breeding, commercial poultry egg incubation, veterinary products, animal ear tagging, and farmer training across Malawi.
                            </p>
                            <p class="text-warning small fw-bold mb-0">
                                "WE ARE BEST AT WHAT WE DO, TRY US AND YOU WON'T REGRET"
                            </p>
                        </div>
                        <div class="col-6 col-md-2">
                            <h6 class="fw-bold text-white mb-3 text-uppercase small">Farm Operations</h6>
                            <ul class="list-unstyled d-flex flex-column gap-2 small text-white-50">
                                <li><a href="<?= url('/flocks') ?>" class="text-decoration-none">Flock Dynamics</a></li>
                                <li><a href="<?= url('/eggs') ?>" class="text-decoration-none">Egg Production</a></li>
                                <li><a href="<?= url('/incubation-jobs') ?>" class="text-decoration-none">Incubation Hub</a></li>
                                <li><a href="<?= url('/sales') ?>" class="text-decoration-none">Farm Sales / POS</a></li>
                                <li><a href="<?= url('/feed-drugs') ?>" class="text-decoration-none">Feed & Vet Products</a></li>
                            </ul>
                        </div>
                        <div class="col-6 col-md-3">
                            <h6 class="fw-bold text-white mb-3 text-uppercase small">Intelligence & Access</h6>
                            <ul class="list-unstyled d-flex flex-column gap-2 small text-white-50">
                                <li><a href="<?= url('/reports') ?>" class="text-decoration-none">Executive Reports</a></li>
                                <li><a href="<?= url('/reports/aged-debtors') ?>" class="text-decoration-none">Aged Debtors Analysis</a></li>
                                <li><a href="<?= url('/profile') ?>" class="text-decoration-none">My Profile & Security</a></li>
                                <li><span class="text-white-50"><i class="bi bi-geo-alt me-1 text-warning"></i>Station: Liwonde, Machinga</span></li>
                                <li><span class="text-white-50"><i class="bi bi-cash me-1 text-warning"></i>Currency: Malawi Kwacha (MWK)</span></li>
                            </ul>
                        </div>
                        <div class="col-12 col-md-3">
                            <h6 class="fw-bold text-white mb-3 text-uppercase small">Contact Details</h6>
                            <ul class="list-unstyled d-flex flex-column gap-2 small text-white-50">
                                <li><i class="bi bi-geo-alt-fill text-warning me-2"></i>Mawira Area by Chipamba Road, Liwonde</li>
                                <li><i class="bi bi-mailbox2 text-warning me-2"></i>P.O. Box 250, Liwonde, Machinga</li>
                                <li><i class="bi bi-telephone-fill text-warning me-2"></i>+265 8888 996 20 / 9998 996 20</li>
                                <li><i class="bi bi-envelope-fill text-warning me-2"></i>icchipeta@gmail.com</li>
                                <li><i class="bi bi-envelope-fill text-warning me-2"></i>icchipeta@yahoo.co.uk</li>
                            </ul>
                        </div>
                    </div>
                    <hr class="border-secondary my-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-white-50">
                        <div>
                            &copy; <?= date('Y') ?> <strong>Msantha Investments and General Suppliers (MIGS)</strong>. All rights reserved.
                        </div>
                        <div>
                            Msantha Poultry Management System &bull; Production v2.0 &bull; Malawi
                        </div>
                    </div>
                </div>
            </footer>
        </main>
    </div>

    <!-- Mobile Bottom Navigation (< 992px) -->
    <nav class="app-bottom-nav d-lg-none" aria-label="Mobile Navigation">
        <div class="nav-item">
            <a href="<?= url('/') ?>" class="nav-link <?= ($activeNav ?? '') === 'home' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span><?= t('nav.home') ?></span>
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= url('/flocks') ?>" class="nav-link <?= ($activeNav ?? '') === 'flocks' ? 'active' : '' ?>">
                <i class="bi bi-feather"></i>
                <span><?= t('nav.flocks') ?></span>
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= url('/eggs') ?>" class="nav-link <?= ($activeNav ?? '') === 'eggs' ? 'active' : '' ?>">
                <i class="bi bi-egg-fried"></i>
                <span><?= t('nav.eggs') ?></span>
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= url('/incubation-jobs') ?>" class="nav-link <?= ($activeNav ?? '') === 'incubation' ? 'active' : '' ?>">
                <i class="bi bi-thermometer-half"></i>
                <span><?= t('nav.incubation') ?></span>
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= url('/more') ?>" class="nav-link <?= ($activeNav ?? '') === 'more' ? 'active' : '' ?>">
                <i class="bi bi-grid-fill"></i>
                <span>More</span>
            </a>
        </div>
    </nav>

    <!-- Global Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Mobile Alert Pop-up Toast (< 768px) -->
    <?php if (!empty($systemAlerts)): ?>
        <?php $topAlert = $systemAlerts[0]; ?>
        <div id="mobileAlertPopup" class="position-fixed d-md-none" style="bottom: 74px; left: 12px; right: 12px; z-index: 1070;">
            <div class="card border-0 shadow-lg rounded-3 text-white <?= $topAlert['type'] === 'danger' ? 'bg-danger' : 'bg-dark' ?> p-2.5">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                        <i class="bi <?= $topAlert['type'] === 'danger' ? 'bi-exclamation-triangle-fill text-warning' : 'bi-bell-fill text-warning' ?> fs-5 flex-shrink-0"></i>
                        <div class="text-truncate">
                            <strong class="d-block small text-white text-truncate"><?= e($topAlert['title']) ?></strong>
                            <small class="text-white-50 d-block text-truncate" style="font-size: 0.72rem;"><?= e($topAlert['message']) ?></small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                        <a href="<?= e($topAlert['link']) ?>" class="btn btn-sm btn-light text-dark fw-bold py-0.5 px-2" style="font-size: 0.7rem;">Open</a>
                        <button type="button" class="btn-close btn-close-white" style="font-size: 0.65rem;" onclick="document.getElementById('mobileAlertPopup').remove()" aria-label="Close"></button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- App Module JS -->
    <script type="module" src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
