<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <?php include __DIR__ . '/_print_header.php'; ?>

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('/reports') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Reports</a>
                <h1 class="h3 fw-bold mb-0 text-dark">Egg Productivity & Tray Yield</h1>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
                    Harvest Analytics
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Daily harvests, broken/consumed losses, incubated counts, and 30-egg tray yield conversions.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/reports/eggs?format=csv&from=' . urlencode($from) . '&to=' . urlencode($to)) ?>" class="btn btn-outline-success btn-sm fw-bold">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export to Excel (CSV)
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm fw-bold shadow-sm">
                <i class="bi bi-printer-fill me-1"></i>Save as PDF / Print
            </button>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom p-3">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-bar-chart-steps me-2 text-primary"></i>Monthly Production Breakdown</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3">Month</th>
                        <th>Breed / Flock</th>
                        <th class="text-end">Eggs Collected</th>
                        <th class="text-end">Broken / Damaged</th>
                        <th class="text-end">Consumed</th>
                        <th class="text-end">Sold Table Eggs</th>
                        <th class="text-end">Incubated</th>
                        <th class="text-end pe-3">Tray Yield (30s)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No egg production records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data as $r): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark"><?= e($r['month']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= e($r['breed']) ?></span></td>
                                <td class="text-end fw-bold text-success"><?= number_format((int)$r['collected']) ?></td>
                                <td class="text-end text-danger"><?= number_format((int)$r['broken']) ?></td>
                                <td class="text-end text-muted"><?= number_format((int)$r['consumed']) ?></td>
                                <td class="text-end text-primary"><?= number_format((int)$r['sold']) ?></td>
                                <td class="text-end text-info"><?= number_format((int)$r['incubated']) ?></td>
                                <td class="text-end pe-3 fw-bold text-primary">
                                    <?= number_format((int)$r['tray_equivalents']) ?> trays <small class="text-muted">(+<?= $r['loose_eggs'] ?>)</small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

