<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <?php include __DIR__ . '/_print_header.php'; ?>

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('/reports') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Reports</a>
                <h1 class="h3 fw-bold mb-0 text-dark">Feed Cost Efficiency per Bird & Egg</h1>
                <span class="badge bg-info-subtle text-info border border-info-subtle fs-6">
                    Input Costing
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Calculates feed expense per bird stocked and production cost per harvested egg.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/reports/feed-efficiency?format=csv&year=' . $year) ?>" class="btn btn-outline-success btn-sm fw-bold">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export to Excel (CSV)
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm fw-bold shadow-sm">
                <i class="bi bi-printer-fill me-1"></i>Save as PDF / Print
            </button>
        </div>
    </div>

    <!-- Efficiency KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-primary">
                <span class="text-muted small fw-semibold text-uppercase">Total Feed Expense</span>
                <div class="fs-4 fw-bold text-primary mt-1"><?= number_format($data['total_feed_cost']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted">Procured in <?= $year ?: 'all years' ?></small>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-success">
                <span class="text-muted small fw-semibold text-uppercase">Total Birds Stocked</span>
                <div class="fs-4 fw-bold text-success mt-1"><?= number_format($data['total_birds_stocked']) ?> <small class="fs-6 text-muted">birds</small></div>
                <small class="text-muted">Intake across all flocks</small>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-warning">
                <span class="text-muted small fw-semibold text-uppercase">Feed Cost per Bird</span>
                <div class="fs-4 fw-bold text-warning mt-1"><?= number_format($data['feed_cost_per_bird']) ?> <small class="fs-6 text-muted">MWK/bird</small></div>
                <small class="text-muted">Average input cost</small>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-info">
                <span class="text-muted small fw-semibold text-uppercase">Feed Cost per Egg</span>
                <div class="fs-4 fw-bold text-info mt-1"><?= number_format($data['feed_cost_per_egg']) ?> <small class="fs-6 text-muted">MWK/egg</small></div>
                <small class="text-muted"><?= number_format($data['total_eggs_produced']) ?> eggs harvested</small>
            </div>
        </div>
    </div>
</div>

