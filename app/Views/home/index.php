<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Welcome Farm Banner -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
        <div class="p-4 p-lg-4 text-white d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3" 
             style="background: linear-gradient(135deg, #0b2914 0%, #14532d 50%, #1b6d39 100%);">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge bg-warning text-dark fw-bold px-2.5 py-1 rounded-pill" style="font-size: 0.72rem;">
                        <i class="bi bi-geo-alt-fill me-1"></i>Liwonde Station
                    </span>
                    <span class="text-white-50 small">&bull; Msantha Investments & General Suppliers &bull; Est. 2006</span>
                </div>
                <h1 class="h3 fw-bold mb-1 text-white">
                    Welcome back, <?= e(\App\Core\Auth::user()['full_name'] ?? 'Farm Manager') ?>!
                </h1>
                <p class="mb-0 text-white-50 small" style="max-width: 650px;">
                    Poultry headcount, egg collection yields, commercial incubation pipelines, and receivable aging across Liwonde station.
                </p>
            </div>
            <!-- Quick Actions Toolbar -->
            <div class="d-flex flex-wrap gap-2">
                <?php if (\App\Core\Auth::can('incubation', 'create')): ?>
                    <a href="<?= url('/incubation/receive') ?>" class="btn btn-warning text-dark fw-bold btn-sm rounded-pill px-3 shadow-sm">
                        <i class="bi bi-plus-circle me-1"></i>Receive Eggs
                    </a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('flocks', 'create')): ?>
                    <a href="<?= url('/flocks/quick-entry') ?>" class="btn btn-light text-dark fw-semibold btn-sm rounded-pill px-3 shadow-sm">
                        <i class="bi bi-pencil-square me-1"></i>Flock Daily Log
                    </a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('eggs', 'create')): ?>
                    <a href="<?= url('/eggs/create') ?>" class="btn btn-outline-light fw-semibold btn-sm rounded-pill px-3">
                        <i class="bi bi-egg-fried me-1"></i>Record Eggs
                    </a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('sales', 'create')): ?>
                    <a href="<?= url('/sales/create') ?>" class="btn btn-outline-light fw-semibold btn-sm rounded-pill px-3">
                        <i class="bi bi-cart-plus me-1"></i>New Sale
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Active Alerts Banner -->
    <?php if (!empty($alerts)): ?>
        <div class="row g-2 mb-4">
            <?php foreach ($alerts as $a): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="alert alert-<?= $a['type'] ?> border-0 shadow-sm rounded-3 d-flex align-items-start gap-2 mb-0 py-2.5 px-3">
                        <i class="bi <?= $a['type'] === 'danger' ? 'bi-exclamation-octagon-fill text-danger' : ($a['type'] === 'warning' ? 'bi-exclamation-triangle-fill text-warning' : 'bi-info-circle-fill text-primary') ?> fs-5 mt-0.5"></i>
                        <div class="flex-grow-1">
                            <strong class="d-block small text-dark"><?= e($a['title']) ?></strong>
                            <span class="small text-muted"><?= e($a['message']) ?></span>
                        </div>
                        <a href="<?= e($a['link']) ?>" class="btn btn-sm btn-outline-dark py-0 px-2 mt-1">View</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Main Operational KPIs (5 Elevated Cards) -->
    <div class="row g-3 mb-4">
        <!-- KPI 1: Live Stock -->
        <div class="col-6 col-md-4 col-xl">
            <a href="<?= url('/flocks') ?>" class="card kpi-card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success text-decoration-none">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Live Stock</span>
                    <div class="p-1.5 bg-success-subtle rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <?= icon('chicken', 18) ?>
                    </div>
                </div>
                <div class="fs-3 fw-bold text-success mt-1 kpi-value"><?= number_format($stats['total_live_stock']) ?></div>
                <small class="text-muted"><?= (int) $stats['active_flocks'] ?> active flocks on farm</small>
            </a>
        </div>

        <!-- KPI 2: Mortality (7d) -->
        <div class="col-6 col-md-4 col-xl">
            <a href="<?= url('/reports/mortality') ?>" class="card kpi-card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-danger text-decoration-none">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Mortality (7d)</span>
                    <div class="p-1.5 bg-danger-subtle rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <?= icon('vaccine', 18) ?>
                    </div>
                </div>
                <div class="fs-3 fw-bold text-danger mt-1 kpi-value"><?= number_format($stats['deaths_this_week']) ?></div>
                <small class="text-muted">Recorded deaths this week</small>
            </a>
        </div>

        <!-- KPI 3: Eggs Collected -->
        <div class="col-6 col-md-4 col-xl">
            <a href="<?= url('/eggs') ?>" class="card kpi-card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary text-decoration-none">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Eggs (7 Days)</span>
                    <div class="p-1.5 bg-primary-subtle rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <?= icon('egg', 18) ?>
                    </div>
                </div>
                <div class="fs-3 fw-bold text-primary mt-1 kpi-value">
                    <?= number_format($stats['egg_trays_this_week']) ?> <small class="fs-6 text-muted">trays</small>
                </div>
                <small class="text-muted"><?= number_format($stats['eggs_this_week']) ?> loose eggs</small>
            </a>
        </div>

        <!-- KPI 4: Incubation Batches -->
        <?php if (\App\Core\Auth::can('incubation', 'view')): ?>
            <div class="col-6 col-md-4 col-xl">
                <a href="<?= url('/incubation-jobs') ?>" class="card kpi-card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-info text-decoration-none">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">In Incubators</span>
                        <div class="p-1.5 bg-info-subtle rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <?= icon('incubator', 18) ?>
                        </div>
                    </div>
                    <div class="fs-3 fw-bold text-info mt-1 kpi-value"><?= number_format($stats['incubating_batches']) ?> <small class="fs-6 text-muted">batches</small></div>
                    <small class="text-muted">Active setting & hatcher units</small>
                </a>
            </div>
        <?php endif; ?>

        <!-- KPI 5: Receivables Due -->
        <?php if (\App\Core\Auth::can('reports', 'view') || \App\Core\Auth::can('finance', 'view')): ?>
            <div class="col-12 col-md-8 col-xl">
                <a href="<?= url('/reports/aged-debtors') ?>" class="card kpi-card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 <?= $stats['unpaid_balances_total'] > 0 ? 'border-warning' : 'border-success' ?> text-decoration-none">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Unpaid Balances</span>
                        <div class="p-1.5 bg-warning-subtle rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <?= icon('debt', 18) ?>
                        </div>
                    </div>
                    <div class="fs-3 fw-bold <?= $stats['unpaid_balances_total'] > 0 ? 'text-danger' : 'text-success' ?> mt-1 kpi-value">
                        <?= number_format($stats['unpaid_balances_total']) ?> <small class="fs-6 text-muted">MWK</small>
                    </div>
                    <small class="text-muted"><?= (int) $stats['debtors_count'] ?> customer account(s) pending</small>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Two-Column Layout -->
    <div class="row g-4">
        <!-- Left Column: Incubation Pipeline & Poultry Financial Position (8 cols) -->
        <div class="col-12 col-lg-8">
            <!-- Incubation Pipeline & Countdown Card -->
            <?php if (\App\Core\Auth::can('incubation', 'view')): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
                <div class="card-header bg-white border-bottom p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-2 bg-info-subtle text-info rounded-3">
                            <i class="bi bi-hourglass-split fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark fs-6 fs-md-5">Hatchery Pipeline & Countdown</h5>
                            <small class="text-muted d-block" style="font-size: 0.75rem;">Active customer batches in setters & hatchers</small>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= url('/incubation/receive') ?>" class="btn btn-outline-success btn-sm rounded-pill px-2.5 py-1" style="font-size: 0.78rem;">
                            <i class="bi bi-plus-circle me-1"></i>Receive Eggs
                        </a>
                        <a href="<?= url('/incubation-jobs') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 py-1" style="font-size: 0.78rem;">
                            All Jobs
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($incubatingBatches)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-egg-fried text-muted display-4 d-block mb-2"></i>
                            <h6 class="fw-bold text-dark">No Active Batches Incubating</h6>
                            <p class="text-muted small mb-3">All previous batches have completed hatching or egg reception is ready.</p>
                            <a href="<?= url('/incubation/receive') ?>" class="btn btn-success btn-sm fw-bold px-3 rounded-pill">
                                Receive First Batch
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="ps-3">Customer & Breed</th>
                                        <th class="text-nowrap">Eggs Set</th>
                                        <th class="d-none d-md-table-cell">Transfer Day (18)</th>
                                        <th class="d-none d-md-table-cell">Expected Hatch</th>
                                        <th class="pe-3 text-end text-nowrap">Countdown</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($incubatingBatches as $jb): ?>
                                        <?php
                                        $daysLeft = (int) ($jb['days_to_hatch'] ?? 0);
                                        $badgeColor = $daysLeft <= 0 ? 'bg-danger text-white' : ($daysLeft <= 3 ? 'bg-warning text-dark' : 'bg-info-subtle text-info border border-info-subtle');
                                        $isInternal = !empty($jb['customer_is_internal']) || stripos((string)($jb['customer_name'] ?? ''), 'msantha') !== false;
                                        ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                                    <span class="fw-bold text-dark"><?= e($jb['customer_name'] ?? 'Customer') ?></span>
                                                    <?php if ($isInternal): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">Owner • Internal</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex align-items-center gap-2 flex-wrap mt-0.5">
                                                    <small class="badge bg-light text-dark border"><?= e($jb['breed_name'] ?? 'Chicken') ?></small>
                                                    <span class="d-inline d-md-none text-muted font-monospace" style="font-size: 0.72rem;">
                                                        <i class="bi bi-calendar-event me-1 text-primary"></i>Hatch: <?= e($jb['expected_hatch_date'] ?? '—') ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="fw-semibold text-dark text-nowrap"><?= number_format((int)$jb['eggs_incubated']) ?></td>
                                            <td class="text-muted small font-monospace d-none d-md-table-cell"><?= e($jb['date_transfer'] ?? '—') ?></td>
                                            <td class="fw-bold text-dark small font-monospace d-none d-md-table-cell"><?= e($jb['expected_hatch_date'] ?? '—') ?></td>
                                            <td class="pe-3 text-end text-nowrap">
                                                <span class="badge <?= $badgeColor ?> fw-bold px-2 py-1 rounded-pill" style="font-size: 0.75rem;">
                                                    <?php if ($daysLeft <= 0): ?>
                                                        <i class="bi bi-bell-fill me-1"></i>Hatch Day!
                                                    <?php else: ?>
                                                        <i class="bi bi-clock me-1"></i>In <?= $daysLeft ?>d
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Poultry Financial Overview (Strictly Poultry Operations) -->
            <?php if (\App\Core\Auth::can('finance', 'view')): ?>
                <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <div class="p-2 bg-success-subtle text-success rounded-3">
                                <i class="bi bi-cash-stack fs-5"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">Poultry Financial Operations</h5>
                                <small class="text-muted">Egg sales, hatchery receipts & feed ledger summary</small>
                            </div>
                        </div>
                        <a href="<?= url('/finance') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                            Full Cash Books &rarr;
                        </a>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <?php 
                            // Highlight the primary poultry business units
                            $poultryUnits = array_filter($cashPerBusiness, function($bu) {
                                return in_array($bu['code'] ?? '', ['POULTRY', 'HATCHERY', 'FEED', 'EGGS'], true) || 
                                       str_contains(strtolower($bu['name'] ?? ''), 'poultry') || 
                                       str_contains(strtolower($bu['name'] ?? ''), 'egg') ||
                                       str_contains(strtolower($bu['name'] ?? ''), 'hatch');
                            });
                            if (empty($poultryUnits)) {
                                $poultryUnits = array_slice($cashPerBusiness, 0, 3);
                            }
                            ?>
                            <?php foreach ($poultryUnits as $bu): ?>
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="p-3 rounded-3 bg-light border h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="text-dark small"><?= e($bu['name']) ?></strong>
                                            <span class="badge bg-white text-muted border" style="font-size: 0.65rem;"><?= e($bu['code']) ?></span>
                                        </div>
                                        <div class="small text-muted mt-2">
                                            <span>Month Net:</span>
                                            <strong class="<?= $bu['net_month'] >= 0 ? 'text-success' : 'text-danger' ?> d-block fs-6">
                                                <?= ($bu['net_month'] >= 0 ? '+' : '') . number_format((int)$bu['net_month']) ?> MWK
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Live Stock by Breed & Quick Shortcuts (4 cols) -->
        <div class="col-12 col-lg-4">
            <!-- Quick Operational Shortcuts Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
                <div class="card-header bg-white border-bottom p-3">
                    <h5 class="fw-bold mb-0 text-dark small text-uppercase">
                        <i class="bi bi-lightning-charge-fill text-warning me-2"></i>Quick Operational Shortcuts
                    </h5>
                </div>
                <div class="card-body p-2">
                    <div class="list-group list-group-flush">
                        <?php if (\App\Core\Auth::can('flocks', 'create')): ?>
                            <a href="<?= url('/flocks/quick-entry') ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2.5 px-3 border-0 rounded-2">
                                <span class="small fw-semibold d-inline-flex align-items-center gap-2">
                                    <?= icon('chicken', 18) ?> Daily Flock Log (Mortality)
                                </span>
                                <i class="bi bi-chevron-right text-muted small"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (\App\Core\Auth::can('flocks', 'view')): ?>
                            <a href="<?= url('/flocks/price-ladder') ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2.5 px-3 border-0 rounded-2">
                                <span class="small fw-semibold d-inline-flex align-items-center gap-2">
                                    <?= icon('ladder', 18) ?> Age-Based Price Ladder
                                </span>
                                <i class="bi bi-chevron-right text-muted small"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (\App\Core\Auth::can('incubation', 'create')): ?>
                            <a href="<?= url('/incubation/receive') ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2.5 px-3 border-0 rounded-2">
                                <span class="small fw-semibold d-inline-flex align-items-center gap-2">
                                    <?= icon('incubator', 18) ?> Receive Customer Eggs
                                </span>
                                <i class="bi bi-chevron-right text-muted small"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (\App\Core\Auth::can('reports', 'view')): ?>
                            <a href="<?= url('/reports/aged-debtors') ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2.5 px-3 border-0 rounded-2">
                                <span class="small fw-semibold d-inline-flex align-items-center gap-2">
                                    <?= icon('debt', 18) ?> Aged Debtors 30/60/90d
                                </span>
                                <i class="bi bi-chevron-right text-muted small"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (in_array(\App\Core\Auth::role(), ['owner', 'manager'], true)): ?>
                            <a href="<?= url('/admin/backups') ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2.5 px-3 border-0 rounded-2">
                                <span class="small fw-semibold d-inline-flex align-items-center gap-2">
                                    <?= icon('backup', 18) ?> Database Backups
                                </span>
                                <i class="bi bi-chevron-right text-muted small"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Stock by Breed Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark small text-uppercase">
                        <i class="bi bi-pie-chart-fill text-primary me-2"></i>Live Stock by Breed
                    </h5>
                    <a href="<?= url('/flocks') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-2" style="font-size: 0.72rem;">View</a>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($stockByBreed)): ?>
                        <p class="text-muted text-center py-3 mb-0 small">No active flocks recorded.</p>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php
                            $totStock = max(1, $stats['total_live_stock']);
                            foreach (array_slice($stockByBreed, 0, 6) as $sb):
                                $share = round(($sb['stock'] / $totStock) * 100, 1);
                            ?>
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold text-dark small"><?= e($sb['breed_name']) ?></span>
                                        <span class="fw-bold text-success small"><?= number_format((int)$sb['stock']) ?></span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $share ?>%" aria-valuenow="<?= $share ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
