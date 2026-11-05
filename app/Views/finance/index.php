<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Flash Messages -->
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= e($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 fw-bold mb-0 text-dark">Multi-Business Cash Books</h1>
                <span class="badge bg-success-subtle text-success border border-success-subtle fs-6">
                    Phase 8 Multi-Ledger
                </span>
                <?php if ($stats['is_locked']): ?>
                    <span class="badge bg-danger text-white fs-6">
                        <i class="bi bi-lock-fill me-1"></i>Month Locked
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-muted small mb-0 mt-1">Multi-unit operations, cash entries, running balances, bank movements, and month-end closes.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="<?= url('/finance/profit-and-loss') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold">
                <i class="bi bi-graph-up me-1"></i>P&L Statement
            </a>
            <?php if ($activeUnit): ?>
                <a href="<?= url('/finance/export-csv?business_unit_id=' . $activeUnit['id'] . (!empty($yearMonth) ? '&month=' . urlencode($yearMonth) : '')) ?>" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
            <?php endif; ?>
            <?php if (\App\Core\Auth::can('finance', 'create')): ?>
                <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addEntryModal" <?= $stats['is_locked'] ? 'disabled title="Month is locked"' : '' ?>>
                    <i class="bi bi-plus-circle me-1"></i>Add Cash Entry
                </button>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#bankMovementModal">
                    <i class="bi bi-bank me-1"></i>Bank Deposit / Withdraw
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Business Unit Tabs -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="<?= url('/finance?unit=all&kind=' . urlencode($kind ?? 'all')) ?>" 
           class="btn btn-sm rounded-pill px-3 <?= ($selectedUnit ?? '') === 'all' ? 'btn-success fw-bold' : 'btn-outline-secondary' ?>">
            <i class="bi bi-grid-fill me-1"></i>All Units
        </a>
        <?php foreach ($units as $u): ?>
            <a href="<?= url('/finance?unit=' . urlencode($u['code']) . '&kind=' . urlencode($kind ?? 'all')) ?>" 
               class="btn btn-sm rounded-pill px-3 <?= ($selectedUnit ?? '') === $u['code'] ? 'btn-success fw-bold' : 'btn-outline-secondary' ?>">
                <?= e($u['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- KPI Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Total Receipts</span>
                    <div class="p-1.5 bg-success-subtle rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                        <?= icon('cash', 16) ?>
                    </div>
                </div>
                <div class="fs-4 fw-bold text-success mt-1">+<?= number_format($stats['total_receipts']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted"><?= $activeUnit ? e($activeUnit['name']) : 'All' ?> inflows</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-danger">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Total Expenses</span>
                    <div class="p-1.5 bg-danger-subtle rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                        <?= icon('debt', 16) ?>
                    </div>
                </div>
                <div class="fs-4 fw-bold text-danger mt-1">-<?= number_format($stats['total_expenses']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted"><?= $activeUnit ? e($activeUnit['name']) : 'All' ?> outflows</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 <?= $stats['net_balance'] >= 0 ? 'border-primary' : 'border-warning' ?>">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Cash Balance</span>
                    <div class="p-1.5 bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                        <i class="bi bi-wallet2" style="font-size: 0.85rem;"></i>
                    </div>
                </div>
                <div class="fs-4 fw-bold <?= $stats['net_balance'] >= 0 ? 'text-primary' : 'text-danger' ?> mt-1">
                    <?= number_format($stats['net_balance']) ?> <small class="fs-6 text-muted">MWK</small>
                </div>
                <small class="text-muted">Net cash position</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Bank Balance</span>
                    <div class="p-1.5 bg-info-subtle text-info rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                        <i class="bi bi-bank" style="font-size: 0.85rem;"></i>
                    </div>
                </div>
                <div class="fs-4 fw-bold text-info mt-1">
                    <?= number_format($stats['bank_balance']) ?> <small class="fs-6 text-muted">MWK</small>
                </div>
                <small class="text-muted">Ledger bank account</small>
            </div>
        </div>
    </div>

    <!-- Month-End Close Card (When Business Unit & Month selected) -->
    <?php if ($activeUnit): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-light">
            <div class="card-body p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle p-2 <?= $stats['is_locked'] ? 'bg-danger text-white' : 'bg-success text-white' ?>">
                        <i class="bi <?= $stats['is_locked'] ? 'bi-lock-fill' : 'bi-unlock-fill' ?> fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">
                            <?= e($activeUnit['name']) ?> Month Status: 
                            <span class="<?= $stats['is_locked'] ? 'text-danger' : 'text-success' ?>">
                                <?= $stats['is_locked'] ? 'LOCKED (Closed)' : 'OPEN FOR EDITING' ?>
                            </span>
                        </div>
                        <small class="text-muted">
                            <?php if ($stats['is_locked']): ?>
                                Month-end closed. Reopening is strictly restricted to the Farm Owner.
                            <?php else: ?>
                                Closing the month prevents further transactions and safeguards the accounts.
                            <?php endif; ?>
                        </small>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <?php if (!$stats['is_locked']): ?>
                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#lockMonthModal">
                            <i class="bi bi-lock me-1"></i>Close & Lock Month
                        </button>
                    <?php else: ?>
                        <?php if (\App\Core\Auth::hasRole('owner')): ?>
                            <form method="POST" action="<?= url('/finance/unlock-month') ?>" onsubmit="return confirm('Owner confirmation: Are you sure you want to reopen this locked month?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="business_unit_id" value="<?= e($activeUnit['id']) ?>">
                                <input type="hidden" name="year_month" value="<?= e($yearMonth ?: date('Y-m')) ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-unlock me-1"></i>Reopen Month (Owner Only)
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="badge bg-secondary py-2 px-3"><i class="bi bi-shield-lock me-1"></i>Locked by Management</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filter & Search Card -->
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
        <form method="GET" action="<?= url('/finance') ?>" class="row g-2 align-items-center">
            <input type="hidden" name="unit" value="<?= e($selectedUnit ?? 'POULTRY') ?>">
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" value="<?= e($search ?? '') ?>" class="form-control form-control-sm border-start-0" placeholder="Search particulars, cheque or voucher...">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <input type="month" name="month" value="<?= e($yearMonth ?? '') ?>" class="form-control form-control-sm" title="Filter by Month">
            </div>
            <div class="col-6 col-md-3">
                <select name="kind" class="form-select form-select-sm">
                    <option value="all" <?= ($kind ?? 'all') === 'all' ? 'selected' : '' ?>>All Entries</option>
                    <option value="transaction" <?= ($kind ?? '') === 'transaction' ? 'selected' : '' ?>>Transactions Only</option>
                    <option value="month_total" <?= ($kind ?? '') === 'month_total' ? 'selected' : '' ?>>Month-End Closes Only</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-success btn-sm w-100 fw-bold rounded-pill">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <?php if (!empty($search) || !empty($yearMonth) || ($kind ?? 'all') !== 'all'): ?>
                    <a href="<?= url('/finance?unit=' . urlencode($selectedUnit ?? 'POULTRY')) ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-2.5" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Ledger Table Container: Desktop (Z-Fold Scan) -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4 d-none d-md-block">
        <div class="table-responsive">
            <table class="table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3 py-3" style="width: 170px;">Date & Unit</th>
                        <th class="py-3">Particulars & Description</th>
                        <th class="py-3" style="width: 150px;">Category</th>
                        <th class="text-end py-3" style="width: 150px;">Inflow (Receipt)</th>
                        <th class="text-end py-3" style="width: 150px;">Outflow (Expense)</th>
                        <th class="text-end pe-3 py-3" style="width: 120px;">Classification</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entries)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-wallet2 display-6 d-block mb-2 text-muted"></i>
                                No ledger records found matching criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($entries as $e): ?>
                            <?php
                            $rec = (int) $e['receipt'];
                            $exp = (int) $e['expenditure'];
                            $isMonthTotal = ($e['entry_kind'] === 'month_total');
                            ?>
                            <tr class="<?= $isMonthTotal ? 'bg-warning-subtle fw-semibold' : '' ?>" style="transition: background-color 0.15s ease;">
                                <td class="ps-3 py-2.5">
                                    <div class="fw-bold text-dark font-monospace small">
                                        <?= !empty($e['entry_date']) ? date('d-M-Y', strtotime($e['entry_date'])) : '—' ?>
                                    </div>
                                    <span class="badge bg-light text-secondary border mt-0.5" style="font-size: 0.65rem;">
                                        <?= e($e['unit_name']) ?>
                                    </span>
                                </td>
                                <td class="py-2.5">
                                    <div class="text-dark fw-semibold"><?= e($e['description'] ?: 'General transaction entry') ?></div>
                                </td>
                                <td class="py-2.5">
                                    <span class="badge bg-light text-dark border fw-normal" style="font-size: 0.72rem;">
                                        <?= e($e['category_name'] ?: 'General') ?>
                                    </span>
                                </td>
                                <td class="text-end py-2.5">
                                    <?php if ($rec > 0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold fs-6">
                                            +<?= number_format($rec) ?> <small style="font-size: 0.65rem;">MWK</small>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end py-2.5">
                                    <?php if ($exp > 0): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold fs-6">
                                            -<?= number_format($exp) ?> <small style="font-size: 0.65rem;">MWK</small>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3 py-2.5">
                                    <?php if ($isMonthTotal): ?>
                                        <span class="badge bg-warning text-dark border border-warning" style="font-size: 0.68rem;">Month Close</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">Direct Entry</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Desktop Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-2 px-3">
                <span class="text-muted small">Showing <?= count($entries) ?> of <?= number_format($totalRows) ?> entries</span>
                <nav aria-label="Finance navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link rounded-start-pill" href="<?= url('/finance?unit=' . urlencode($selectedUnit) . '&page=' . ($page - 1) . '&kind=' . urlencode($kind) . '&q=' . urlencode($search) . '&month=' . urlencode($yearMonth)) ?>">Previous</a>
                        </li>
                        <li class="page-item active"><span class="page-link"><?= $page ?> / <?= $totalPages ?></span></li>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link rounded-end-pill" href="<?= url('/finance?unit=' . urlencode($selectedUnit) . '&page=' . ($page + 1) . '&kind=' . urlencode($kind) . '&q=' . urlencode($search) . '&month=' . urlencode($yearMonth)) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>

    <!-- Mobile Folded Card View (< 768px - Z-Fold Method) -->
    <div class="d-flex flex-column gap-2.5 d-md-none mb-4">
        <?php if (empty($entries)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center text-muted bg-white">
                No ledger records found.
            </div>
        <?php else: ?>
            <?php foreach ($entries as $e): ?>
                <?php
                $rec = (int) $e['receipt'];
                $exp = (int) $e['expenditure'];
                $isMonthTotal = ($e['entry_kind'] === 'month_total');
                ?>
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white <?= $isMonthTotal ? 'border-start border-4 border-warning' : '' ?>">
                    <!-- Top Z-Line: Date + Unit (Left) & Kind (Right) -->
                    <div class="d-flex justify-content-between align-items-center mb-1.5">
                        <div class="d-flex align-items-center gap-1.5">
                            <span class="fw-bold font-monospace small text-dark"><?= !empty($e['entry_date']) ? date('d-M-Y', strtotime($e['entry_date'])) : '—' ?></span>
                            <span class="badge bg-light text-muted border" style="font-size: 0.65rem;"><?= e($e['unit_name']) ?></span>
                        </div>
                        <span class="badge <?= $isMonthTotal ? 'bg-warning text-dark' : 'bg-light text-secondary border' ?>" style="font-size: 0.65rem;">
                            <?= $isMonthTotal ? 'Month Close' : 'Transaction' ?>
                        </span>
                    </div>

                    <!-- Middle: Particulars / Description -->
                    <div class="fw-semibold text-dark small mb-1.5"><?= e($e['description'] ?: 'General transaction') ?></div>

                    <!-- Bottom Z-Line: Category (Left) & Amount (Right) -->
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <span class="badge bg-light text-dark border fw-normal" style="font-size: 0.68rem;"><?= e($e['category_name'] ?: 'General') ?></span>
                        <div>
                            <?php if ($rec > 0): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold fs-6">
                                    +<?= number_format($rec) ?> <small style="font-size: 0.65rem;">MWK</small>
                                </span>
                            <?php elseif ($exp > 0): ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold fs-6">
                                    -<?= number_format($exp) ?> <small style="font-size: 0.65rem;">MWK</small>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">0 MWK</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Mobile Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-center mt-2">
                    <nav aria-label="Mobile finance pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link rounded-start-pill" href="<?= url('/finance?unit=' . urlencode($selectedUnit) . '&page=' . ($page - 1) . '&kind=' . urlencode($kind) . '&q=' . urlencode($search) . '&month=' . urlencode($yearMonth)) ?>">Prev</a>
                            </li>
                            <li class="page-item active"><span class="page-link"><?= $page ?> / <?= $totalPages ?></span></li>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link rounded-end-pill" href="<?= url('/finance?unit=' . urlencode($selectedUnit) . '&page=' . ($page + 1) . '&kind=' . urlencode($kind) . '&q=' . urlencode($search) . '&month=' . urlencode($yearMonth)) ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Bank Movements Section (Z-Fold Layout) -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
        <div class="card-header bg-white border-bottom p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <div class="p-1.5 bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                    <i class="bi bi-bank" style="font-size: 0.85rem;"></i>
                </div>
                <h5 class="card-title fw-bold mb-0 text-dark fs-6">Recent Bank Movements</h5>
            </div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold px-3 py-1.5 rounded-pill" style="font-size: 0.78rem;">
                Bank Position: <?= number_format($stats['bank_balance']) ?> MWK
            </span>
        </div>
        <div class="table-responsive">
            <table class="table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3 py-2.5" style="width: 140px;">Date</th>
                        <th class="py-2.5" style="width: 140px;">Movement Kind</th>
                        <th class="text-end py-2.5" style="width: 150px;">Movement Amount</th>
                        <th class="text-end py-2.5" style="width: 150px;">Balance After</th>
                        <th class="pe-3 py-2.5">Remarks / Cheque #</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bankMovements)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No bank movements recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($bankMovements as $bm): ?>
                            <tr>
                                <td class="ps-3 py-2.5 font-monospace fw-bold text-dark small">
                                    <?= date('d-M-Y', strtotime($bm['move_date'])) ?>
                                </td>
                                <td class="py-2.5">
                                    <?php if ($bm['kind'] === 'deposit'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold" style="font-size: 0.72rem;">
                                            <i class="bi bi-arrow-down-left me-1"></i>Deposit
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold" style="font-size: 0.72rem;">
                                            <i class="bi bi-arrow-up-right me-1"></i>Withdrawal
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end py-2.5 fw-bold <?= $bm['kind'] === 'deposit' ? 'text-success' : 'text-danger' ?>">
                                    <?= $bm['kind'] === 'deposit' ? '+' : '-' ?><?= number_format((int)$bm['amount']) ?> <small class="text-muted" style="font-size: 0.7rem;">MWK</small>
                                </td>
                                <td class="text-end py-2.5 font-monospace text-dark fw-semibold">
                                    <?= !empty($bm['balance_after']) ? number_format((int)$bm['balance_after']) . ' MWK' : '—' ?>
                                </td>
                                <td class="pe-3 py-2.5 text-muted small"><?= e($bm['remarks'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add Cash Entry -->
<div class="modal fade" id="addEntryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/finance/entries') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Cash Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Business Unit</label>
                    <select name="business_unit_id" class="form-select" required>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($activeUnit && $activeUnit['id'] == $u['id']) ? 'selected' : '' ?>>
                                <?= e($u['name']) ?> (<?= e($u['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Transaction Date</label>
                    <input type="date" name="entry_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Particulars / Description</label>
                    <input type="text" name="description" class="form-control" placeholder="e.g. Sales of eggs, Purchase of feed" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold text-success">Receipt (MWK)</label>
                        <input type="number" name="receipt" class="form-control" value="0" min="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold text-danger">Expenditure (MWK)</label>
                        <input type="number" name="expenditure" class="form-control" value="0" min="0">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Expense Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">General / None</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold">Record Transaction</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Bank Movement -->
<div class="modal fade" id="bankMovementModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/finance/bank-movements') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Record Bank Movement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Date</label>
                    <input type="date" name="move_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Movement Type</label>
                    <select name="kind" class="form-select" required>
                        <option value="deposit">Bank Deposit (Inflow to Bank)</option>
                        <option value="withdrawal">Bank Withdrawal (Outflow from Bank)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Amount (MWK)</label>
                    <input type="number" name="amount" class="form-control" min="1" placeholder="Amount in integer MWK" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Remarks / Reference</label>
                    <input type="text" name="remarks" class="form-control" placeholder="Cheque #, deposit slip, cash withdrawal reason">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">Save Movement</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Lock Month -->
<?php if ($activeUnit): ?>
<div class="modal fade" id="lockMonthModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('/finance/lock-month') ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="business_unit_id" value="<?= e($activeUnit['id']) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-warning"><i class="bi bi-lock me-1"></i>Close & Lock Month</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">
                    Locking the month closes the cashbook for <strong><?= e($activeUnit['name']) ?></strong> for the chosen month.
                    Once locked, no user except the <strong>Farm Owner</strong> can reopen it.
                </p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Month to Lock (YYYY-MM)</label>
                    <input type="month" name="year_month" class="form-control" value="<?= e($yearMonth ?: date('Y-m')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Closing Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Month-end reconciled with bank and physical cash"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning fw-bold">Lock Month Now</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
