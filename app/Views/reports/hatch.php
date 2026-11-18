<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <?php include __DIR__ . '/_print_header.php'; ?>

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('/reports') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Reports</a>
                <h1 class="h3 fw-bold mb-0 text-dark">Hatch Rate % Performance</h1>
                <span class="badge bg-warning-subtle text-dark border border-warning-subtle fs-6">
                    Incubation Analytics
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Hatchability ratios (chicks hatched ÷ eggs incubated) analyzed by breed, customer, and intake month.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/reports/hatch?format=csv&group_by=' . urlencode($groupBy) . '&from=' . urlencode($from) . '&to=' . urlencode($to)) ?>" class="btn btn-outline-success btn-sm fw-bold">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export to Excel (CSV)
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm fw-bold shadow-sm">
                <i class="bi bi-printer-fill me-1"></i>Save as PDF / Print
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom p-3">
            <form method="GET" action="<?= url('/reports/hatch') ?>" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Group By</label>
                    <select name="group_by" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="breed" <?= $groupBy === 'breed' ? 'selected' : '' ?>>Group by Breed</option>
                        <option value="customer" <?= $groupBy === 'customer' ? 'selected' : '' ?>>Group by Customer</option>
                        <option value="month" <?= $groupBy === 'month' ? 'selected' : '' ?>>Group by Intake Month</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">From Date</label>
                    <input type="date" name="from" value="<?= e($from) ?>" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">To Date</label>
                    <input type="date" name="to" value="<?= e($to) ?>" class="form-control form-control-sm">
                </div>
                <div class="col-auto align-self-end">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-filter me-1"></i>Filter</button>
                    <?php if (!empty($from) || !empty($to) || $groupBy !== 'breed'): ?>
                        <a href="<?= url('/reports/hatch') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3"><?= ucwords($groupBy) ?></th>
                        <th class="text-center">Batches Count</th>
                        <th class="text-end">Eggs Incubated</th>
                        <th class="text-end">Infertile Eggs</th>
                        <th class="text-end">Hatched Chicks</th>
                        <th class="text-end pe-3">Hatch Rate %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No incubation records found for the selected criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data as $r): ?>
                            <?php
                            $rate = (float) $r['hatch_rate'];
                            $badge = $rate >= 60 ? 'bg-success' : ($rate >= 40 ? 'bg-primary' : ($rate >= 25 ? 'bg-warning text-dark' : 'bg-danger'));
                            ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark"><?= e($r['label']) ?></td>
                                <td class="text-center"><?= number_format((int)$r['total_batches']) ?></td>
                                <td class="text-end fw-semibold"><?= number_format((int)$r['total_incubated']) ?></td>
                                <td class="text-end text-muted"><?= number_format((int)$r['total_infertile']) ?></td>
                                <td class="text-end fw-bold text-success"><?= number_format((int)$r['total_hatched']) ?></td>
                                <td class="text-end pe-3">
                                    <span class="badge <?= $badge ?> fs-6"><?= number_format($rate, 1) ?>%</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

