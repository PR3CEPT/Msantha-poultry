<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('/reports') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Reports</a>
                <h1 class="h3 fw-bold mb-0 text-dark">Incubation Statutory Allocation</h1>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
                    Cost Split 25/15/30/20/10
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Automatic allocation of incubation fee receipts: Electricity (25%), Water (15%), Labour (30%), Telephone (20%), and Profit (10%).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/reports/incubation-allocation?format=csv&year=' . $year) ?>" class="btn btn-outline-success btn-sm">
                <i class="bi bi-download me-1"></i>Export CSV
            </a>
            <button onclick="window.print()" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-printer me-1"></i>Print Report
            </button>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom p-3">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-pie-chart me-2 text-primary"></i>Allocation Summary (<?= $year ?>)</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3">Month</th>
                        <th class="text-end">Electricity (25%)</th>
                        <th class="text-end">Water (15%)</th>
                        <th class="text-end">Labour (30%)</th>
                        <th class="text-end">Telephone (20%)</th>
                        <th class="text-end">Profit (10%)</th>
                        <th class="text-end pe-3">Total Allocated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['monthly_rows'])): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No incubation allocations found for <?= $year ?>.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data['monthly_rows'] as $r): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark"><?= e($r['month_key']) ?></td>
                                <td class="text-end"><?= number_format((int)$r['electricity']) ?> MWK</td>
                                <td class="text-end"><?= number_format((int)$r['water']) ?> MWK</td>
                                <td class="text-end"><?= number_format((int)$r['labour']) ?> MWK</td>
                                <td class="text-end"><?= number_format((int)$r['telephone']) ?> MWK</td>
                                <td class="text-end text-success fw-semibold"><?= number_format((int)$r['profit']) ?> MWK</td>
                                <td class="text-end pe-3 fw-bold text-primary"><?= number_format((int)$r['total_month']) ?> MWK</td>
                            </tr>
                        <?php endforeach; ?>
                        <!-- Grand Totals -->
                        <tr class="table-light fw-bold">
                            <td class="ps-3">TOTAL (<?= $year ?>)</td>
                            <td class="text-end"><?= number_format($data['totals']['electricity']) ?> MWK</td>
                            <td class="text-end"><?= number_format($data['totals']['water']) ?> MWK</td>
                            <td class="text-end"><?= number_format($data['totals']['labour']) ?> MWK</td>
                            <td class="text-end"><?= number_format($data['totals']['telephone']) ?> MWK</td>
                            <td class="text-end text-success"><?= number_format($data['totals']['profit']) ?> MWK</td>
                            <td class="text-end pe-3 text-primary fs-6"><?= number_format($data['totals']['grand_total']) ?> MWK</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

