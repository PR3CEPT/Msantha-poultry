<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <?php include __DIR__ . '/_print_header.php'; ?>

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('/reports') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Reports</a>
                <h1 class="h3 fw-bold mb-0 text-dark">Flock Mortality % Analysis</h1>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-6">
                    Flock Dynamics
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Batch intake, mortality rates, cumulative deaths, and surviving headcounts across all species.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/reports/mortality?format=csv' . (!empty($from) ? '&from=' . urlencode($from) : '') . (!empty($to) ? '&to=' . urlencode($to) : '')) ?>" class="btn btn-outline-success btn-sm fw-bold">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export to Excel (CSV)
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm fw-bold shadow-sm">
                <i class="bi bi-printer-fill me-1"></i>Save as PDF / Print
            </button>
        </div>
    </div>

    <!-- Date Range Filter Form -->
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4 d-print-none">
        <form method="GET" action="<?= url('/reports/mortality') ?>" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <span class="small fw-semibold text-dark"><i class="bi bi-calendar-range me-1 text-primary"></i>Flock Start Date Filter:</span>
            </div>
            <div class="col-6 col-md-3">
                <input type="date" name="from" value="<?= e($from ?? '') ?>" class="form-control form-control-sm" placeholder="From Date">
            </div>
            <div class="col-6 col-md-3">
                <input type="date" name="to" value="<?= e($to ?? '') ?>" class="form-control form-control-sm" placeholder="To Date">
            </div>
            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold"><i class="bi bi-funnel me-1"></i>Filter</button>
                <?php if (!empty($from) || !empty($to)): ?>
                    <a href="<?= url('/reports/mortality') ?>" class="btn btn-outline-secondary btn-sm" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-heart-pulse me-2 text-danger"></i>Flock Intake & Mortality Metrics</h5>
            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                Showing <?= count($flocks) ?> of <?= number_format($totalRows ?? count($flocks)) ?> Flocks Analyzed
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase font-monospace" style="font-size: 0.72rem;">
                    <tr>
                        <th class="ps-3 py-3">Batch Code</th>
                        <th class="py-3">Breed</th>
                        <th class="py-3" style="width: 120px;">Start Date</th>
                        <th class="text-end py-3" style="width: 110px;">Start Count</th>
                        <th class="text-end py-3" style="width: 110px;">Total Deaths</th>
                        <th class="text-end py-3" style="width: 110px;">Total Sold</th>
                        <th class="text-end py-3" style="width: 120px;">Current Stock</th>
                        <th class="text-center py-3" style="width: 110px;">Mortality %</th>
                        <th class="text-center pe-3 py-3" style="width: 95px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($flocks)): ?>
                        <tr><td colspan="9" class="text-center py-5 text-muted">No flock batches found matching criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($flocks as $f): ?>
                            <?php
                            $rate = (float) $f['mortality_rate'];
                            $badgeClass = $rate >= 5.0 ? 'bg-danger text-white' : ($rate >= 2.0 ? 'bg-warning text-dark' : 'bg-success text-white');
                            ?>
                            <tr>
                                <td class="ps-3 font-monospace">
                                    <a href="<?= url("/flocks/{$f['flock_id']}") ?>" class="fw-bold text-dark text-decoration-none">
                                        <?= e($f['batch_code']) ?>
                                    </a>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= e($f['breed_name']) ?></span></td>
                                <td class="font-monospace small text-muted"><?= date('d-M-Y', strtotime($f['start_date'])) ?></td>
                                <td class="text-end font-monospace text-muted small"><?= number_format((int)$f['start_count']) ?></td>
                                <td class="text-end font-monospace text-danger fw-semibold">-<?= number_format((int)$f['total_deaths']) ?></td>
                                <td class="text-end font-monospace text-success fw-semibold"><?= number_format((int)$f['total_sold']) ?></td>
                                <td class="text-end font-monospace fw-bold text-dark fs-6"><?= number_format((int)$f['current_stock']) ?></td>
                                <td class="text-center font-monospace">
                                    <span class="badge <?= $badgeClass ?> fw-bold px-2 py-1"><?= number_format($rate, 2) ?>%</span>
                                </td>
                                <td class="text-center pe-3">
                                    <span class="badge <?= $f['is_active'] ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-secondary border' ?> fw-semibold">
                                        <?= $f['is_active'] ? 'Active' : 'Closed' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (!empty($totalPages) && $totalPages > 1): ?>
            <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center p-3">
                <span class="text-muted small">
                    Showing page <?= $page ?> of <?= $totalPages ?> (<?= number_format($totalRows) ?> total batches)
                </span>
                <nav aria-label="Mortality report navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('/reports/mortality?page=' . ($page - 1) . '&from=' . urlencode($from ?? '') . '&to=' . urlencode($to ?? '')) ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        <li class="page-item active">
                            <span class="page-link"><?= $page ?></span>
                        </li>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('/reports/mortality?page=' . ($page + 1) . '&from=' . urlencode($from ?? '') . '&to=' . urlencode($to ?? '')) ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

