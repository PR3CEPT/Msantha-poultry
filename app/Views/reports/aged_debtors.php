<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <?php include __DIR__ . '/_print_header.php'; ?>

    <!-- Header -->
    <div
        class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('/reports') ?>" class="btn btn-outline-secondary btn-sm"><i
                        class="bi bi-arrow-left"></i> Reports</a>
                <h1 class="h3 fw-bold mb-0 text-dark">Aged Debtors & Outstanding Receivables</h1>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-6">
                    Credit & Collections
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Aging analysis of unpaid customer balances across 0-30, 31-60, 61-90,
                and 90+ days intervals.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/reports/aged-debtors?format=csv') ?>" class="btn btn-outline-success btn-sm">
                <i class="bi bi-download me-1"></i>Export CSV
            </a>
            <button onclick="window.print()" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-printer me-1"></i>Print Report
            </button>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-danger"></i>Debtor Aging Ledger
            </h5>
            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                Showing <?= count($debtors) ?> of <?= number_format($totalRows ?? count($debtors)) ?> Debtors On File
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-monospace">
                <thead class="table-light text-muted small text-uppercase" style="font-size: 0.72rem;">
                    <tr>
                        <th class="ps-3 py-3" style="font-family: inherit;">Customer Name</th>
                        <th class="py-3" style="font-family: inherit;">Mobile Phone</th>
                        <th class="text-end py-3">0-30 Days</th>
                        <th class="text-end py-3">31-60 Days</th>
                        <th class="text-end py-3">61-90 Days</th>
                        <th class="text-end py-3">Over 90 Days</th>
                        <th class="text-end pe-3 py-3">Total Due (MWK)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($debtors)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted font-sans-serif">No outstanding debtor
                                accounts found. All payments settled!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($debtors as $d): ?>
                            <tr>
                                <td class="ps-3 font-monospace">
                                    <a href="<?= url("/customers/{$d['customer_id']}") ?>"
                                        class="fw-bold text-dark text-decoration-none">
                                        <?= e($d['name']) ?>
                                    </a>
                                </td>
                                <td class="small text-muted"><?= e($d['phone']) ?></td>
                                <td class="text-end text-muted small">
                                    <?= $d['current_30'] > 0 ? number_format($d['current_30']) : '—' ?>
                                </td>
                                <td class="text-end text-warning-emphasis fw-semibold">
                                    <?= $d['aged_31_60'] > 0 ? number_format($d['aged_31_60']) : '—' ?>
                                </td>
                                <td class="text-end text-danger fw-semibold">
                                    <?= $d['aged_61_90'] > 0 ? number_format($d['aged_61_90']) : '—' ?>
                                </td>
                                <td class="text-end text-danger fw-bold">
                                    <?= $d['aged_over_90'] > 0 ? number_format($d['aged_over_90']) : '—' ?>
                                </td>
                                <td class="text-end pe-3 fw-bold text-danger fs-6">
                                    <?= number_format($d['total_due']) ?> MWK
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <!-- Grand Totals Row -->
                        <?php if (!empty($totals)): ?>
                            <tr class="table-light fw-bold">
                                <td class="ps-3" colspan="2">TOTAL RECEIVABLES (ALL ACCOUNTS)</td>
                                <td class="text-end"><?= number_format($totals['tot30']) ?></td>
                                <td class="text-end text-warning-emphasis"><?= number_format($totals['tot60']) ?></td>
                                <td class="text-end text-danger"><?= number_format($totals['tot90']) ?></td>
                                <td class="text-end text-danger"><?= number_format($totals['totOver']) ?></td>
                                <td class="text-end pe-3 text-danger fs-6"><?= number_format($totals['grandTot']) ?> MWK</td>
                            </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (!empty($totalPages) && $totalPages > 1): ?>
            <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center p-3">
                <span class="text-muted small">
                    Showing page <?= $page ?> of <?= $totalPages ?> (<?= number_format($totalRows) ?> total debtors)
                </span>
                <nav aria-label="Aged debtors navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('/reports/aged-debtors?page=' . ($page - 1)) ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        <li class="page-item active">
                            <span class="page-link"><?= $page ?></span>
                        </li>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('/reports/aged-debtors?page=' . ($page + 1)) ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>