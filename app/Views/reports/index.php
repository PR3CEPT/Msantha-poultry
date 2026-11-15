<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 fw-bold mb-0 text-dark">Management Reports & Analytics</h1>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
                    Audit & Reporting
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Multi-dimensional operational analytics, profitability, flock dynamics, incubation hatch yield, and aged debtors.</p>
        </div>
        <div>
            <!-- Year Selector Form -->
            <form method="GET" action="<?= url('/reports') ?>" class="d-flex gap-2 align-items-center">
                <label class="small text-muted fw-semibold">Year:</label>
                <select name="year" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                    <?php for ($y = 2026; $y >= 2022; $y--): ?>
                        <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- Quick Timeframe Filter Bar (Daily, Weekly, Monthly, Annual) -->
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="small fw-bold text-dark text-uppercase" style="font-size: 0.75rem;">
                    <i class="bi bi-calendar-range text-primary me-1"></i>Quick Timeframe Presets:
                </span>
                <?php
                $today = date('Y-m-d');
                $weekAgo = date('Y-m-d', strtotime('-7 days'));
                $monthStart = date('Y-m-01');
                $monthEnd = date('Y-m-t');
                $yearStart = "{$year}-01-01";
                $yearEnd = "{$year}-12-31";
                ?>
                <a href="<?= url('/reports/sales-by-item?from=' . $today . '&to=' . $today) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold">
                    <i class="bi bi-clock me-1 text-success"></i>Daily (Today)
                </a>
                <a href="<?= url('/reports/sales-by-item?from=' . $weekAgo . '&to=' . $today) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold">
                    <i class="bi bi-calendar-week me-1 text-primary"></i>Weekly (Past 7 Days)
                </a>
                <a href="<?= url('/reports/sales-by-item?from=' . $monthStart . '&to=' . $monthEnd) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold">
                    <i class="bi bi-calendar-month me-1 text-info"></i>Monthly (This Month)
                </a>
                <a href="<?= url('/finance/profit-and-loss?year=' . $year) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold">
                    <i class="bi bi-calendar3 me-1 text-warning"></i>Annual (<?= $year ?>)
                </a>
            </div>
            <div class="small text-muted font-monospace" style="font-size: 0.72rem;">
                Customizable to day, week, month, or year
            </div>
        </div>
    </div>

    <!-- Top Summary Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 <?= $pnl['net_profit'] >= 0 ? 'border-success' : 'border-danger' ?>">
                <span class="text-muted small fw-semibold text-uppercase">Net Multi-Unit Operating Profit</span>
                <div class="fs-4 fw-bold <?= $pnl['net_profit'] >= 0 ? 'text-success' : 'text-danger' ?> mt-1">
                    <?= $pnl['net_profit'] >= 0 ? '+' : '' ?><?= number_format($pnl['net_profit']) ?> <small class="fs-6 text-muted">MWK</small>
                </div>
                <small class="text-muted"><?= $year ?> net margin across all business units</small>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-info">
                <span class="text-muted small fw-semibold text-uppercase">Feed Cost / Bird</span>
                <div class="fs-4 fw-bold text-info mt-1">
                    <?= number_format($feed['feed_cost_per_bird']) ?> <small class="fs-6 text-muted">MWK/bird</small>
                </div>
                <small class="text-muted">Egg cost: <?= number_format($feed['feed_cost_per_egg']) ?> MWK/egg</small>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 <?= $totalOverdue > 0 ? 'border-warning' : 'border-success' ?>">
                <span class="text-muted small fw-semibold text-uppercase">Aged Receivables Outstanding</span>
                <div class="fs-4 fw-bold <?= $totalOverdue > 0 ? 'text-danger' : 'text-success' ?> mt-1">
                    <?= number_format($totalOverdue) ?> <small class="fs-6 text-muted">MWK</small>
                </div>
                <small class="text-muted"><?= $debtorsCount ?> customer debtors on file</small>
            </div>
        </div>
    </div>

    <!-- Reports Directory Cards Grid -->
    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-grid-fill me-2 text-primary"></i>Standard Management Reports</h5>
    <div class="row g-3">
        <!-- 1. Monthly P&L -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-cash-stack fs-4 text-success"></i>
                        <h6 class="fw-bold text-dark mb-0">1. Monthly Profit & Loss</h6>
                    </div>
                    <p class="text-muted small mb-3">Consolidated monthly revenue, expenditure, and net margin across poultry, bees, and fleet operations.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/finance/profit-and-loss?year=' . $year) ?>" class="btn btn-outline-success btn-sm w-100 fw-semibold">View P&L Report</a>
                </div>
            </div>
        </div>

        <!-- 2. Flock Mortality -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-heart-pulse fs-4 text-danger"></i>
                        <h6 class="fw-bold text-dark mb-0">2. Flock Mortality %</h6>
                    </div>
                    <p class="text-muted small mb-3">Intake count, cumulative deaths, mortality percentage, and stock balances per batch.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/reports/mortality') ?>" class="btn btn-outline-danger btn-sm w-100 fw-semibold">View Mortality Report</a>
                </div>
            </div>
        </div>

        <!-- 3. Hatch Rate % -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-egg-fill fs-4 text-warning"></i>
                        <h6 class="fw-bold text-dark mb-0">3. Hatch Rate % Performance</h6>
                    </div>
                    <p class="text-muted small mb-3">Hatching percentage by breed, customer, and month with candling fertility breakdowns.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/reports/hatch') ?>" class="btn btn-outline-warning text-dark btn-sm w-100 fw-semibold">View Hatching Report</a>
                </div>
            </div>
        </div>

        <!-- 4. Egg Productivity -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-bar-chart-steps fs-4 text-primary"></i>
                        <h6 class="fw-bold text-dark mb-0">4. Egg Productivity & Yield</h6>
                    </div>
                    <p class="text-muted small mb-3">Daily harvests, broken/consumed eggs, loose vs standard 30-egg tray conversions.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/reports/eggs') ?>" class="btn btn-outline-primary btn-sm w-100 fw-semibold">View Egg Yield Report</a>
                </div>
            </div>
        </div>

        <!-- 5. Feed Cost Efficiency -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-calculator fs-4 text-info"></i>
                        <h6 class="fw-bold text-dark mb-0">5. Feed Cost Efficiency</h6>
                    </div>
                    <p class="text-muted small mb-3">Feed procurement expense divided by birds stocked and eggs produced.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/reports/feed-efficiency?year=' . $year) ?>" class="btn btn-outline-info btn-sm w-100 fw-semibold">View Efficiency Report</a>
                </div>
            </div>
        </div>

        <!-- 6. Aged Debtors -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-clock-history fs-4 text-secondary"></i>
                        <h6 class="fw-bold text-dark mb-0">6. Aged Debtors Analysis</h6>
                    </div>
                    <p class="text-muted small mb-3">Outstanding customer receivables aged into 0-30, 31-60, 61-90, and 90+ days intervals.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/reports/aged-debtors') ?>" class="btn btn-outline-secondary btn-sm w-100 fw-semibold">View Debtors Report</a>
                </div>
            </div>
        </div>

        <!-- 7. Sales by Item -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-cart-check fs-4 text-success"></i>
                        <h6 class="fw-bold text-dark mb-0">7. Sales Revenue by Item</h6>
                    </div>
                    <p class="text-muted small mb-3">Commercial sales volume and MWK revenue split across live birds, table eggs, fertile eggs, and manure.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/reports/sales-by-item') ?>" class="btn btn-outline-success btn-sm w-100 fw-semibold">View Sales by Item</a>
                </div>
            </div>
        </div>

        <!-- 8. Incubation Statutory Allocation -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-pie-chart fs-4 text-primary"></i>
                        <h6 class="fw-bold text-dark mb-0">8. Statutory Incubation Allocations</h6>
                    </div>
                    <p class="text-muted small mb-3">Statutory cost split: Electricity (25%), Water (15%), Labour (30%), Telephone (20%), and Profit (10%).</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/reports/incubation-allocation?year=' . $year) ?>" class="btn btn-outline-primary btn-sm w-100 fw-semibold">View Allocations Report</a>
                </div>
            </div>
        </div>
    </div>
</div>

