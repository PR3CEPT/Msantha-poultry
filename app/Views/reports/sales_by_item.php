<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <?php include __DIR__ . '/_print_header.php'; ?>

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('/reports') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Reports</a>
                <h1 class="h3 fw-bold mb-0 text-dark">Commercial Sales by Product Line</h1>
                <span class="badge bg-success-subtle text-success border border-success-subtle fs-6">
                    Sales Breakdown
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Aggregated order counts, volume sold, and revenue generated across all farm commodities.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/reports/sales-by-item?format=csv&from=' . urlencode($from) . '&to=' . urlencode($to)) ?>" class="btn btn-outline-success btn-sm fw-bold">
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
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-cart-check me-2 text-success"></i>Product Line Performance</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3">Product Line</th>
                        <th class="text-center">Order Count</th>
                        <th class="text-end">Total Quantity Sold</th>
                        <th class="text-end pe-3">Total Revenue (MWK)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">No commercial sales items recorded.</td></tr>
                    <?php else: ?>
                        <?php
                        $totQty = 0; $totRev = 0;
                        foreach ($items as $i):
                            $totQty += $i['total_quantity'];
                            $totRev += $i['total_revenue'];
                        ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-light text-dark border fs-6"><?= e($i['label']) ?></span>
                                </td>
                                <td class="text-center fw-semibold"><?= number_format((int)$i['orders_count']) ?></td>
                                <td class="text-end fw-semibold text-dark"><?= number_format((int)$i['total_quantity']) ?></td>
                                <td class="text-end pe-3 fw-bold text-success">
                                    +<?= number_format((int)$i['total_revenue']) ?> MWK
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <!-- Total Row -->
                        <tr class="table-light fw-bold">
                            <td class="ps-3" colspan="2">TOTAL REVENUE</td>
                            <td class="text-end"><?= number_format($totQty) ?> units</td>
                            <td class="text-end pe-3 text-success fs-6">+<?= number_format($totRev) ?> MWK</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

