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

    <!-- Manager Custom Date Range Report Generator -->
    <?php if (\App\Core\Auth::can('reports', 'view')): ?>
    <div class="card border-0 shadow-sm rounded-4 p-3.5 bg-white mb-4 border-top border-4 border-success">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="fw-bold text-dark mb-0 fs-6">
                <i class="bi bi-sliders text-success me-2"></i>Custom Date Range Report Generator
            </h5>
            <span class="badge bg-success-subtle text-success border border-success-subtle">Any Date Range</span>
        </div>
        <p class="text-muted small mb-3">Produce customized management reports for any specific date, week, month, or custom period.</p>

        <form method="GET" action="<?= url('/reports/sales-by-item') ?>" id="customReportForm" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-dark mb-1">Select Report Type</label>
                <select id="reportTypeSelect" class="form-select form-select-sm" onchange="updateReportAction(this.value)">
                    <option value="/reports/sales-by-item" selected>Commercial Sales Revenue by Item</option>
                    <option value="/reports/eggs">Egg Production & Yield Breakdown</option>
                    <option value="/reports/hatch">Incubation Hatch Rate % Performance</option>
                    <option value="/reports/mortality">Flock Mortality % Analysis</option>
                    <option value="/reports/aged-debtors">Aged Customer Debtors</option>
                    <option value="/finance/pnl">Profit & Loss Operating Statement</option>
                    <option value="/reports/feed-efficiency">Feed Cost Efficiency</option>
                    <option value="/reports/incubation-allocation">Statutory Incubation Allocations</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold text-dark mb-1">From Date</label>
                <input type="date" name="from" id="reportFromDate" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold text-dark mb-1">To Date</label>
                <input type="date" name="to" id="reportToDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-success btn-sm w-100 fw-bold">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i>Generate
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <script>
    function updateReportAction(route) {
        var form = document.getElementById('customReportForm');
        form.action = '<?= url('') ?>' + route;
    }
    </script>

    <!-- Top Summary Metrics Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. Core Poultry Farm Operating Profit -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="text-muted small fw-semibold text-uppercase">Poultry Farm Profit</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 0.68rem;">Core Farm</span>
                </div>
                <div class="fs-4 fw-bold text-success font-monospace mt-1">
                    <?= ($pnl['poultry_profit'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($pnl['poultry_profit'] ?? 0) ?> <small class="fs-6 text-muted font-sans-serif">MWK</small>
                </div>
                <div class="small text-muted font-monospace mt-1" style="font-size: 0.72rem;">
                    In: +<?= number_format($pnl['poultry_receipts'] ?? 0) ?> | Out: -<?= number_format($pnl['poultry_expenses'] ?? 0) ?>
                </div>
            </div>
        </div>

        <!-- 2. Multi-Unit Consolidated Net Operating Profit -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 <?= ($pnl['net_profit'] ?? 0) >= 0 ? 'border-success' : 'border-danger' ?>">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="text-muted small fw-semibold text-uppercase">Multi-Unit Net Profit</span>
                    <a href="<?= url('/finance/profit-and-loss?year=' . $year) ?>" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill text-decoration-none" style="font-size: 0.68rem;" title="View business unit breakdown">
                        All Units &rarr;
                    </a>
                </div>
                <div class="fs-4 fw-bold <?= ($pnl['net_profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?> font-monospace mt-1">
                    <?= ($pnl['net_profit'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($pnl['net_profit'] ?? 0) ?> <small class="fs-6 text-muted font-sans-serif">MWK</small>
                </div>
                <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                    <?= $year ?> net across farm &amp; car hire fleet
                </div>
            </div>
        </div>

        <!-- 3. Feed Cost / Bird -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-info">
                <span class="text-muted small fw-semibold text-uppercase">Feed Cost / Bird</span>
                <div class="fs-4 fw-bold text-info font-monospace mt-1">
                    <?= number_format($feed['feed_cost_per_bird']) ?> <small class="fs-6 text-muted font-sans-serif">MWK/bird</small>
                </div>
                <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                    Egg cost: <span class="font-monospace fw-semibold"><?= number_format($feed['feed_cost_per_egg']) ?> MWK</span>/egg
                </div>
            </div>
        </div>

        <!-- 4. Aged Receivables Outstanding -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 <?= $totalOverdue > 0 ? 'border-warning' : 'border-success' ?>">
                <span class="text-muted small fw-semibold text-uppercase">Aged Receivables</span>
                <div class="fs-4 fw-bold <?= $totalOverdue > 0 ? 'text-danger' : 'text-success' ?> font-monospace mt-1">
                    <?= number_format($totalOverdue) ?> <small class="fs-6 text-muted font-sans-serif">MWK</small>
                </div>
                <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                    <?= $debtorsCount ?> active debtor account<?= $debtorsCount === 1 ? '' : 's' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Explanatory Multi-Unit Breakdown Banner -->
    <?php
    $fleetLoss = abs(($pnl['by_unit']['CAR_MZ9350']['net'] ?? 0) + ($pnl['by_unit']['CAR_MZ8170']['net'] ?? 0));
    ?>
    <?php if (($pnl['net_profit'] ?? 0) < 0 && ($pnl['poultry_profit'] ?? 0) > 0): ?>
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3.5 mb-4 border-start border-4 border-info">
        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-start gap-3">
                <div class="rounded-circle bg-info-subtle text-info p-2.5 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                    <i class="bi bi-info-circle-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-1">Why is the Multi-Unit Margin negative (-<?= number_format(abs($pnl['net_profit'] ?? 0)) ?> MWK)?</h6>
                    <p class="text-muted small mb-0">
                        Your <strong>Core Poultry Farm is in profit (+<?= number_format($pnl['poultry_profit'] ?? 0) ?> MWK)</strong>. The multi-unit figure is negative because it consolidates all business lines including the <strong>Car Hire Fleet</strong>, which had <strong>MK <?= number_format($fleetLoss) ?></strong> in major vehicle repairs, mechanic charges, and spare parts (vehicles MZ 9350 &amp; MZ 8170).
                    </p>
                </div>
            </div>
            <a href="<?= url('/finance/profit-and-loss?year=' . $year) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 text-nowrap fw-semibold align-self-start align-self-md-center">
                <i class="bi bi-bar-chart-steps me-1"></i>View Unit P&amp;L
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Reports Directory Cards Grid -->
    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-grid-fill me-2 text-primary"></i>Standard Management Reports</h5>
    <div class="row g-3">
        <!-- 1. Monthly P&L -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-cash-stack fs-4 text-success"></i>
                        <h6 class="fw-bold text-dark mb-0">1. Monthly Profit & Loss</h6>
                    </div>
                    <p class="text-muted small mb-3">Consolidated monthly revenue, expenditure, and net margin across poultry, bees, and fleet operations.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/finance/profit-and-loss?year=' . $year) ?>" class="btn btn-outline-success btn-sm w-100 fw-semibold rounded-pill">View P&L Report</a>
                </div>
            </div>
        </div>

        <!-- 2. Flock Mortality -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-heart-pulse fs-4 text-danger"></i>
                        <h6 class="fw-bold text-dark mb-0">2. Flock Mortality %</h6>
                    </div>
                    <p class="text-muted small mb-3">Intake count, cumulative deaths, mortality percentage, and stock balances per batch.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/reports/mortality') ?>" class="btn btn-outline-danger btn-sm w-100 fw-semibold rounded-pill">View Mortality Report</a>
                </div>
            </div>
        </div>

        <!-- 3. Hatch Rate % -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-egg-fill fs-4 text-warning"></i>
                        <h6 class="fw-bold text-dark mb-0">3. Hatch Rate % Performance</h6>
                    </div>
                    <p class="text-muted small mb-3">Hatching percentage by breed, customer, and month with candling fertility breakdowns.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/reports/hatch') ?>" class="btn btn-outline-warning text-dark btn-sm w-100 fw-semibold rounded-pill">View Hatching Report</a>
                </div>
            </div>
        </div>

        <!-- 4. Egg Productivity -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-bar-chart-steps fs-4 text-primary"></i>
                        <h6 class="fw-bold text-dark mb-0">4. Egg Productivity & Yield</h6>
                    </div>
                    <p class="text-muted small mb-3">Daily harvests, broken/consumed eggs, loose vs standard 30-egg tray conversions.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/reports/eggs') ?>" class="btn btn-outline-primary btn-sm w-100 fw-semibold rounded-pill">View Egg Yield Report</a>
                </div>
            </div>
        </div>

        <!-- 5. Feed Cost Efficiency -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-calculator fs-4 text-info"></i>
                        <h6 class="fw-bold text-dark mb-0">5. Feed Cost Efficiency</h6>
                    </div>
                    <p class="text-muted small mb-3">Feed procurement expense divided by birds stocked and eggs produced.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/reports/feed-efficiency?year=' . $year) ?>" class="btn btn-outline-info btn-sm w-100 fw-semibold rounded-pill">View Efficiency Report</a>
                </div>
            </div>
        </div>

        <!-- 6. Aged Debtors -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-clock-history fs-4 text-secondary"></i>
                        <h6 class="fw-bold text-dark mb-0">6. Aged Debtors Analysis</h6>
                    </div>
                    <p class="text-muted small mb-3">Outstanding customer receivables aged into 0-30, 31-60, 61-90, and 90+ days intervals.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/reports/aged-debtors') ?>" class="btn btn-outline-secondary btn-sm w-100 fw-semibold rounded-pill">View Debtors Report</a>
                </div>
            </div>
        </div>

        <!-- 7. Sales by Item -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-cart-check fs-4 text-success"></i>
                        <h6 class="fw-bold text-dark mb-0">7. Sales Revenue by Item</h6>
                    </div>
                    <p class="text-muted small mb-3">Commercial sales volume and MWK revenue split across live birds, table eggs, fertile eggs, and manure.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/reports/sales-by-item') ?>" class="btn btn-outline-success btn-sm w-100 fw-semibold rounded-pill">View Sales by Item</a>
                </div>
            </div>
        </div>

        <!-- 8. Incubation Statutory Allocation -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-pie-chart fs-4 text-primary"></i>
                        <h6 class="fw-bold text-dark mb-0">8. Statutory Incubation Allocations</h6>
                    </div>
                    <p class="text-muted small mb-3">Statutory cost split: Electricity (25%), Water (15%), Labour (30%), Telephone (20%), and Profit (10%).</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/reports/incubation-allocation?year=' . $year) ?>" class="btn btn-outline-primary btn-sm w-100 fw-semibold rounded-pill">View Allocations Report</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateReportAction(val) {
    var form = document.getElementById('customReportForm');
    if (form) {
        var base = <?= json_encode(rtrim(url('/'), '/')) ?>;
        form.action = base + val;
    }
}
</script>

