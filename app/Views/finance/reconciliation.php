<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <?php include dirname(__DIR__) . '/reports/_print_header.php'; ?>

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('/finance?unit=' . urlencode($bu['code'] ?? 'POULTRY')) ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i>Back to Cash Book
                </a>
                <h1 class="h3 fw-bold mb-0 text-dark">Historical Ledger Reconciliation</h1>
                <span class="badge bg-success-subtle text-success border border-success-subtle fs-6">
                    <?= e($bu['name'] ?? 'POULTRY') ?>
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Audit verification table reconciling historical monthly cashbook totals against ledger entry line items.</p>
        </div>

        <!-- Business Unit switcher -->
        <div class="d-flex gap-2">
            <div class="dropdown">
                <button class="btn btn-outline-primary btn-sm rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Switch Unit: <?= e($bu['name'] ?? '') ?>
                </button>
                <ul class="dropdown-menu shadow border-0 rounded-3">
                    <?php foreach ($units as $u): ?>
                        <li><a class="dropdown-item py-2" href="<?= url('/finance/reconcile?business_unit_id=' . $u['id']) ?>"><?= e($u['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <button onclick="window.print()" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                <i class="bi bi-printer me-1"></i>Print Report
            </button>
        </div>
    </div>

    <!-- Reconciliation Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0 fs-6"><i class="bi bi-check2-circle me-2 text-success"></i>Audited Month-End Accounting Blocks</h5>
            <span class="badge bg-light text-secondary border"><?= count($reconciliation) ?> Blocks Verified</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3">Accounting Block</th>
                        <th class="text-end">Ledger Receipts</th>
                        <th class="text-end">System Receipts</th>
                        <th class="text-end">Ledger Expenses</th>
                        <th class="text-end">System Expenses</th>
                        <th class="text-center">Receipt Variance</th>
                        <th class="text-center">Expense Variance</th>
                        <th class="text-center pe-3">Reconciliation Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reconciliation)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">No month-total verification rows found for this business unit.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reconciliation as $r): ?>
                            <tr class="<?= $r['is_reconciled'] ? '' : 'table-warning' ?>">
                                <td class="ps-3 fw-bold text-dark">
                                    Month Block #<?= $r['block'] ?>
                                </td>
                                <td class="text-end fw-semibold"><?= number_format((int)$r['excel_receipt']) ?> MWK</td>
                                <td class="text-end text-success fw-bold"><?= number_format((int)$r['system_receipt']) ?> MWK</td>
                                <td class="text-end fw-semibold"><?= number_format((int)$r['excel_expenditure']) ?> MWK</td>
                                <td class="text-end text-danger fw-bold"><?= number_format((int)$r['system_expenditure']) ?> MWK</td>
                                <td class="text-center">
                                    <?php if ($r['diff_receipt'] === 0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">0 MWK</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><?= ($r['diff_receipt'] > 0 ? '+' : '') . number_format($r['diff_receipt']) ?> MWK</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($r['diff_expenditure'] === 0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">0 MWK</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><?= ($r['diff_expenditure'] > 0 ? '+' : '') . number_format($r['diff_expenditure']) ?> MWK</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center pe-3">
                                    <?php if ($r['is_reconciled']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle me-1"></i>Reconciled 100%</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-info-circle me-1"></i>Variance</span>
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
