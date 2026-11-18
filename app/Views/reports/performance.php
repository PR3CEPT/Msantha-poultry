<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('/reports') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Reports Hub</a>
                <h1 class="h3 fw-bold mb-0 text-dark">Executive Decision & Business Performance</h1>
                <span class="badge bg-success-subtle text-success border border-success-subtle fs-6">
                    <i class="bi bi-graph-up-arrow me-1"></i>Intelligence
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Strategic analytics for decision making: Best sellers, slow-moving items, top consumers, profit & loss, and flock mortality financial impact.
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= url('/reports/performance?format=csv' . (!empty($from) ? '&from=' . urlencode($from) : '') . (!empty($to) ? '&to=' . urlencode($to) : '') . ($year ? '&year=' . $year : '')) ?>" class="btn btn-outline-success fw-bold btn-sm">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export to Excel (CSV)
            </a>
            <a href="<?= url('/reports/performance?format=pdf' . (!empty($from) ? '&from=' . urlencode($from) : '') . (!empty($to) ? '&to=' . urlencode($to) : '') . ($year ? '&year=' . $year : '')) ?>" target="_blank" class="btn btn-primary fw-bold btn-sm shadow-sm">
                <i class="bi bi-printer-fill me-1"></i>Official PDF / Print
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
        <form method="GET" action="<?= url('/reports/performance') ?>" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold text-dark mb-1">From Date</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= e($from ?? '') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold text-dark mb-1">To Date</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= e($to ?? '') ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-dark mb-1">Or Quick Year</label>
                <select name="year" class="form-select form-select-sm">
                    <option value="">-- All Years --</option>
                    <?php for ($y = 2026; $y >= 2022; $y--): ?>
                        <option value="<?= $y ?>" <?= ((int)($year ?? 0) === $y) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-6 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm fw-bold flex-grow-1">
                    <i class="bi bi-filter me-1"></i>Filter Analytics
                </button>
                <a href="<?= url('/reports/performance') ?>" class="btn btn-outline-secondary btn-sm" title="Reset">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Executive KPI Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                <span class="text-muted small fw-semibold text-uppercase">Gross Inflow (Revenue)</span>
                <div class="fs-4 fw-bold text-success mt-1 font-monospace"><?= number_format((int)$report['financials']['gross_revenue']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted">Direct sales + Incubation receipts</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-danger">
                <span class="text-muted small fw-semibold text-uppercase">Operating Expenses</span>
                <div class="fs-4 fw-bold text-danger mt-1 font-monospace"><?= number_format((int)$report['financials']['operating_expenses']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted">Feed, utilities, medicine & labour</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-warning">
                <span class="text-muted small fw-semibold text-uppercase">Mortality Loss (MWK)</span>
                <div class="fs-4 fw-bold text-warning-emphasis mt-1 font-monospace"><?= number_format((int)$report['financials']['mortality_loss_mwk']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted"><?= number_format((int)$report['financials']['mortality_birds_lost']) ?> birds lost &times; ~4,500 MWK</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary">
                <span class="text-muted small fw-semibold text-uppercase">Net Margin & Profit</span>
                <div class="fs-4 fw-bold text-primary mt-1 font-monospace"><?= number_format((int)$report['financials']['net_profit']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-success fw-bold"><?= $report['financials']['profit_margin_pct'] ?>% net operating margin</small>
            </div>
        </div>
    </div>

    <!-- Management Decision & Problem Solving Insights -->
    <?php if (!empty($report['insights'])): ?>
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden border-top border-4 border-warning">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark fs-6">
                <i class="bi bi-lightbulb-fill text-warning me-2"></i>Executive Decision Insights & Problem Solving
            </h5>
            <span class="badge bg-warning-subtle text-dark border border-warning-subtle">Automated Advisory</span>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <?php foreach ($report['insights'] as $ins): ?>
                    <div class="col-12 col-md-4">
                        <div class="p-3 rounded-3 border bg-light h-100">
                            <span class="badge bg-<?= e($ins['type']) ?> text-white mb-2"><?= e($ins['title']) ?></span>
                            <p class="small text-dark mb-0"><?= e($ins['text']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 1. Best Sellers & Product Lines -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0 text-dark fs-6">
                    <i class="bi bi-trophy-fill text-warning me-2"></i>Product Performance & Best Sellers vs Slow Moving
                </h5>
                <small class="text-muted">Ranked by revenue contribution with percentage share of commercial receipts</small>
            </div>
            <a href="<?= url('/reports/sales-by-item') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5">
                Item Line Detail &rarr;
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase" style="font-size: 0.72rem;">
                    <tr>
                        <th class="ps-3">Product Line</th>
                        <th class="text-center">Orders / Batches</th>
                        <th class="text-end">Units Sold</th>
                        <th class="text-end">Avg Realized Price</th>
                        <th class="text-end">Total Revenue (MWK)</th>
                        <th class="text-end">% Share</th>
                        <th class="text-center pe-3">Performance Class</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report['all_products'] as $idx => $p): ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold text-dark"><?= e($p['label']) ?></div>
                            </td>
                            <td class="text-center font-monospace"><?= number_format((int)$p['orders_count']) ?></td>
                            <td class="text-end font-monospace"><?= number_format((int)$p['total_units']) ?> <small class="text-muted"><?= e($p['unit_name']) ?></small></td>
                            <td class="text-end font-monospace"><?= number_format((int)$p['avg_price']) ?> MWK</td>
                            <td class="text-end font-monospace fw-bold text-dark"><?= number_format((int)$p['total_revenue']) ?> MWK</td>
                            <td class="text-end font-monospace fw-bold text-primary"><?= $p['share_pct'] ?>%</td>
                            <td class="text-center pe-3">
                                <?php if ($idx === 0 && (int)$p['total_revenue'] > 0): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-star-fill me-1"></i>Best Seller #1
                                    </span>
                                <?php elseif ((int)$p['total_revenue'] > 0): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                        Active Revenue
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1">
                                        Slow / Zero Movement
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. High Consumers / Top Customers -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0 text-dark fs-6">
                    <i class="bi bi-person-lines-fill text-primary me-2"></i>High Consumers & Top 10 Customer Accounts
                </h5>
                <small class="text-muted">Top spending clients across retail commodity purchases and incubation batches</small>
            </div>
            <a href="<?= url('/reports/aged-debtors') ?>" class="btn btn-sm btn-outline-danger rounded-pill px-2.5">
                Debtors Analysis &rarr;
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase" style="font-size: 0.72rem;">
                    <tr>
                        <th class="ps-3">Customer Name</th>
                        <th>Contact / Phone</th>
                        <th class="text-center">POS Orders</th>
                        <th class="text-center">Incubation Batches</th>
                        <th class="text-end">Total Spend (MWK)</th>
                        <th class="text-end pe-3">Outstanding Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report['top_customers'])): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No customer transactions found for this period.</td></tr>
                    <?php else: ?>
                        <?php foreach ($report['top_customers'] as $c): ?>
                            <tr>
                                <td class="ps-3">
                                    <a href="<?= url("/customers/{$c['id']}") ?>" class="fw-bold text-dark text-decoration-none">
                                        <?= e($c['name']) ?>
                                    </a>
                                    <?php if (!empty($c['location'])): ?>
                                        <small class="text-muted d-block"><?= e($c['location']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="small font-monospace text-muted"><?= e($c['phone'] ?: '—') ?></td>
                                <td class="text-center font-monospace"><?= number_format((int)$c['sales_orders']) ?></td>
                                <td class="text-center font-monospace"><?= number_format((int)$c['inc_batches']) ?></td>
                                <td class="text-end font-monospace fw-bold text-dark"><?= number_format((int)$c['total_spend']) ?> MWK</td>
                                <td class="text-end pe-3 font-monospace">
                                    <?php if ((int)$c['balance_due'] > 0): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold">
                                            <?= number_format((int)$c['balance_due']) ?> MWK Due
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            Settled
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
