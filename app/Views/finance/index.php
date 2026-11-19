<?php declare(strict_types=1); ?>

<style>
    /* Cash Books & Finance Dedicated Executive Theme */
    .finance-kpi-card {
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .finance-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08) !important;
    }

    .unit-tab-pill {
        transition: all 0.18s ease;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .unit-tab-pill.active {
        background: linear-gradient(135deg, #14532d 0%, #1b6d39 100%) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(27, 109, 57, 0.25);
        border-color: #1b6d39 !important;
    }
</style>

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
    <div
        class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
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
            <p class="text-muted small mb-0 mt-1">Multi-unit operations, cash entries, running balances, bank movements,
                and month-end closes.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="<?= url('/finance/profit-and-loss') ?>"
                class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold">
                <i class="bi bi-graph-up me-1"></i>P&L Statement
            </a>
            <?php if ($activeUnit): ?>
                <a href="<?= url('/finance/export-csv?business_unit_id=' . $activeUnit['id'] . (!empty($yearMonth) ? '&month=' . urlencode($yearMonth) : '')) ?>"
                    class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export to Excel (CSV)
                </a>
            <?php endif; ?>
            <?php if (\App\Core\Auth::can('finance', 'create')): ?>
                <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal"
                    data-bs-target="#addEntryModal" <?= $stats['is_locked'] ? 'disabled title="Month is locked"' : '' ?>>
                    <i class="bi bi-plus-circle me-1"></i>Add Cash Entry
                </button>
            <?php endif; ?>
            <?php if (\App\Core\Auth::hasRole(['owner', 'manager'])): ?>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold" data-bs-toggle="modal"
                    data-bs-target="#bankMovementModal">
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
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Total
                        Receipts</span>
                    <div class="p-1.5 bg-success-subtle rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width: 28px; height: 28px;">
                        <?= icon('cash', 16) ?>
                    </div>
                </div>
                <div class="fs-4 fw-bold text-success mt-1">+<?= number_format($stats['total_receipts']) ?> <small
                        class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted"><?= $activeUnit ? e($activeUnit['name']) : 'All' ?> inflows</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-danger">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Total
                        Expenses</span>
                    <div class="p-1.5 bg-danger-subtle rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width: 28px; height: 28px;">
                        <?= icon('debt', 16) ?>
                    </div>
                </div>
                <div class="fs-4 fw-bold text-danger mt-1">-<?= number_format($stats['total_expenses']) ?> <small
                        class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted"><?= $activeUnit ? e($activeUnit['name']) : 'All' ?> outflows</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div
                class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 <?= $stats['net_balance'] >= 0 ? 'border-primary' : 'border-warning' ?>">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Cash
                        Balance</span>
                    <div class="p-1.5 bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width: 28px; height: 28px;">
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
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Bank
                        Balance</span>
                    <div class="p-1.5 bg-info-subtle text-info rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width: 28px; height: 28px;">
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
                    <div
                        class="rounded-circle p-2 <?= $stats['is_locked'] ? 'bg-danger text-white' : 'bg-success text-white' ?>">
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
                        <?php if (\App\Core\Auth::hasRole(['owner', 'manager'])): ?>
                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                data-bs-target="#lockMonthModal">
                                <i class="bi bi-lock me-1"></i>Close & Lock Month
                            </button>
                        <?php else: ?>
                            <span class="badge bg-light text-muted border py-2 px-3"><i class="bi bi-unlock me-1"></i>Month Open</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (\App\Core\Auth::hasRole('owner')): ?>
                            <form method="POST" action="<?= url('/finance/unlock-month') ?>"
                                onsubmit="return confirm('Owner confirmation: Are you sure you want to reopen this locked month?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="business_unit_id" value="<?= e($activeUnit['id']) ?>">
                                <input type="hidden" name="year_month" value="<?= e($yearMonth ?: date('Y-m')) ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-unlock me-1"></i>Reopen Month (Owner Only)
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="badge bg-secondary py-2 px-3"><i class="bi bi-shield-lock me-1"></i>Locked by
                                Management</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filter & Search Card with Date Ranges -->
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
        <form method="GET" action="<?= url('/finance') ?>" class="row g-2 align-items-center">
            <input type="hidden" name="unit" value="<?= e($selectedUnit ?? 'POULTRY') ?>">
            <div class="col-12 col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" value="<?= e($search ?? '') ?>"
                        class="form-control form-control-sm border-start-0"
                        placeholder="Search particulars, cheque...">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="date_from" value="<?= e($dateFrom ?? '') ?>" class="form-control form-control-sm"
                    title="Start Date" placeholder="From Date">
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="date_to" value="<?= e($dateTo ?? '') ?>" class="form-control form-control-sm"
                    title="End Date" placeholder="To Date">
            </div>
            <div class="col-6 col-md-2">
                <input type="month" name="month" value="<?= e($yearMonth ?? '') ?>" class="form-control form-control-sm"
                    title="Or select Month">
            </div>
            <div class="col-6 col-md-2">
                <select name="kind" class="form-select form-select-sm">
                    <option value="all" <?= ($kind ?? 'all') === 'all' ? 'selected' : '' ?>>All Entries</option>
                    <option value="transaction" <?= ($kind ?? '') === 'transaction' ? 'selected' : '' ?>>Transactions Only</option>
                    <option value="month_total" <?= ($kind ?? '') === 'month_total' ? 'selected' : '' ?>>Month Closes Only</option>
                </select>
            </div>
            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-success btn-sm w-100 fw-bold rounded-pill" title="Apply Filter">
                    <i class="bi bi-funnel"></i>
                </button>
                <?php if (!empty($search) || !empty($yearMonth) || !empty($dateFrom) || !empty($dateTo) || ($kind ?? 'all') !== 'all'): ?>
                    <a href="<?= url('/finance?unit=' . urlencode($selectedUnit ?? 'POULTRY')) ?>"
                        class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
            </div>
        </form>
    </div>

    <!-- Z-Fold Financial Ledger Header & Controls -->
    <!-- Cash Book Ledger Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase font-monospace" style="font-size: 0.72rem;">
                    <tr>
                        <th class="ps-3 py-3" style="width: 120px;">Date</th>
                        <th class="py-3" style="width: 140px;">Business Unit</th>
                        <th class="py-3">Particulars / Description</th>
                        <th class="py-3" style="width: 120px;">Category</th>
                        <th class="py-3" style="width: 110px;">Reference</th>
                        <th class="text-end py-3" style="width: 130px;">Receipt (+In)</th>
                        <th class="text-end py-3" style="width: 130px;">Expenditure (-Out)</th>
                        <th class="text-end pe-3 py-3" style="width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entries)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-wallet2 fs-2 d-block mb-2 text-secondary"></i>
                                No ledger records found matching the filter criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($entries as $e): ?>
                            <?php
                            $rec = (int) $e['receipt'];
                            $exp = (int) $e['expenditure'];
                            $isMonthTotal = ($e['entry_kind'] === 'month_total');
                            $rowClass = $isMonthTotal ? 'table-warning fw-semibold' : '';
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td class="ps-3 font-monospace small text-dark fw-semibold">
                                    <?= !empty($e['entry_date']) ? date('d-M-Y', strtotime($e['entry_date'])) : '—' ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= e($e['unit_name']) ?></span>
                                    <?php if ($isMonthTotal): ?>
                                        <span class="badge bg-warning text-dark border border-warning" style="font-size: 0.65rem;">Month Close</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-dark fw-medium small"><?= e($e['description'] ?: 'General transaction entry') ?></div>
                                    <?php if (!empty($e['notes'])): ?>
                                        <small class="text-muted" style="font-size: 0.72rem;"><?= e($e['notes']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border small"><?= e($e['category_name'] ?: 'General') ?></span>
                                </td>
                                <td class="font-monospace small text-muted">
                                    <?php
                                    $ref = $e['legacy_ref'] ?? $e['reference'] ?? $e['voucher_no'] ?? $e['cheque_no'] ?? '';
                                    if ($ref && preg_match('/^[A-Z0-9_]+:row_(\d+)$/i', $ref, $rm)) {
                                        $ref = 'REF-' . $rm[1];
                                    }
                                    ?>
                                    <?= e($ref ?: '—') ?>
                                </td>
                                <td class="text-end font-monospace">
                                    <?php if ($rec > 0): ?>
                                        <span class="fw-bold text-success">+<?= number_format($rec) ?> MWK</span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end font-monospace">
                                    <?php if ($exp > 0): ?>
                                        <span class="fw-bold text-danger">-<?= number_format($exp) ?> MWK</span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="<?= url('/finance/receipt?id=' . $e['id']) ?>" target="_blank"
                                        class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5" style="font-size: 0.72rem;">
                                        <i class="bi bi-receipt"></i> Print
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center p-3">
                <span class="text-muted small">
                    Showing <?= count($entries) ?> of <?= number_format($totalRows) ?> entries (Page <?= $page ?> of <?= $totalPages ?>)
                </span>
                <nav aria-label="Finance navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('/finance?unit=' . urlencode($selectedUnit) . '&page=' . ($page - 1) . '&kind=' . urlencode($kind) . '&q=' . urlencode($search) . '&month=' . urlencode($yearMonth) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo)) ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        <li class="page-item active"><span class="page-link"><?= $page ?> / <?= $totalPages ?></span></li>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('/finance?unit=' . urlencode($selectedUnit) . '&page=' . ($page + 1) . '&kind=' . urlencode($kind) . '&q=' . urlencode($search) . '&month=' . urlencode($yearMonth) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo)) ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bank Movements Section (Z-Fold Layout) -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
        <div
            class="card-header bg-white border-bottom p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <div class="p-1.5 bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                    style="width: 28px; height: 28px;">
                    <i class="bi bi-bank" style="font-size: 0.85rem;"></i>
                </div>
                <h5 class="card-title fw-bold mb-0 text-dark fs-6">Recent Bank Movements</h5>
            </div>
            <span
                class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold px-3 py-1.5 rounded-pill"
                style="font-size: 0.78rem;">
                Bank Position: <?= number_format($stats['bank_balance']) ?> MWK
            </span>
        </div>
        <div class="p-3">
            <?php if (empty($bankMovements)): ?>
                <div class="text-center py-4 text-muted small bg-light rounded-3">
                    <i class="bi bi-bank fs-4 d-block mb-1 text-muted"></i>
                    No bank movements recorded yet.
                </div>
            <?php else: ?>
                <div class="row g-2">
                    <?php foreach ($bankMovements as $bm): ?>
                        <?php $isDeposit = ($bm['kind'] === 'deposit'); ?>
                        <div class="col-12 col-md-6">
                            <div
                                class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 <?= $isDeposit ? 'border-success' : 'border-danger' ?>">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <span
                                            class="badge <?= $isDeposit ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> fw-bold px-2 py-1"
                                            style="font-size: 0.72rem;">
                                            <i
                                                class="bi <?= $isDeposit ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?> me-1"></i>
                                            <?= $isDeposit ? 'Bank Deposit' : 'Bank Withdrawal' ?>
                                        </span>
                                        <span class="font-monospace text-muted small" style="font-size: 0.78rem;">
                                            <?= date('d-M-Y', strtotime($bm['move_date'])) ?>
                                        </span>
                                    </div>
                                    <span
                                        class="fw-bold fs-6 <?= $isDeposit ? 'text-success' : 'text-danger' ?> font-monospace">
                                        <?= $isDeposit ? '+' : '-' ?>        <?= number_format((int) $bm['amount']) ?> <small
                                            class="text-muted" style="font-size: 0.68rem;">MWK</small>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                    <small class="text-muted text-truncate" style="max-width: 65%;">
                                        <?= e($bm['remarks'] ?: 'No remarks recorded') ?>
                                    </small>
                                    <small class="font-monospace text-dark fw-semibold" style="font-size: 0.75rem;">
                                        Balance:
                                        <?= !empty($bm['balance_after']) ? number_format((int) $bm['balance_after']) . ' MWK' : '—' ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
                    <input type="text" name="description" class="form-control"
                        placeholder="e.g. Sales of eggs, Purchase of feed" required>
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
                    <input type="number" name="amount" class="form-control" min="1" placeholder="Amount in integer MWK"
                        required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Remarks / Reference</label>
                    <input type="text" name="remarks" class="form-control"
                        placeholder="Cheque #, deposit slip, cash withdrawal reason">
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
                        Locking the month closes the cashbook for <strong><?= e($activeUnit['name']) ?></strong> for the
                        chosen month.
                        Once locked, no user except the <strong>Farm Owner</strong> can reopen it.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Month to Lock (YYYY-MM)</label>
                        <input type="month" name="year_month" class="form-control"
                            value="<?= e($yearMonth ?: date('Y-m')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Closing Notes</label>
                        <textarea name="notes" class="form-control" rows="2"
                            placeholder="e.g. Month-end reconciled with bank and physical cash"></textarea>
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
