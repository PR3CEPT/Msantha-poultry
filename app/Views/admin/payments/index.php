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

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h1 class="h3 fw-bold mb-0 text-dark">PayChangu Online Payments</h1>
                <span class="badge <?= $mode === 'live' ? 'bg-success' : 'bg-warning text-dark' ?> rounded-pill px-3 py-1.5 fw-semibold" style="font-size: 0.78rem;">
                    <i class="bi bi-shield-check me-1"></i><?= strtoupper($mode) ?> MODE
                </span>
                <?php if (!empty($publicKey)): ?>
                    <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1 font-monospace" style="font-size: 0.72rem;" title="PayChangu Public Key">
                        <i class="bi bi-key-fill text-warning me-1"></i><?= substr($publicKey, 0, 7) ?>...<?= substr($publicKey, -6) ?>
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-muted small mb-0 mt-1">Real-time mobile money and debit card receipts processed via PayChangu Malawi gateway.</p>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-success">
                <span class="text-muted small fw-semibold text-uppercase">Total Settled Volume</span>
                <div class="fs-4 fw-bold text-success mt-1">+<?= number_format($stats['total_volume']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted">Settled online payments</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-primary">
                <span class="text-muted small fw-semibold text-uppercase">Successful Transactions</span>
                <div class="fs-4 fw-bold text-primary mt-1"><?= number_format($stats['success_count']) ?></div>
                <small class="text-muted">Completed checkouts</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-warning">
                <span class="text-muted small fw-semibold text-uppercase">Pending Checkouts</span>
                <div class="fs-4 fw-bold text-warning mt-1"><?= number_format($stats['pending_count']) ?></div>
                <small class="text-muted">Awaiting payer confirmation</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-danger">
                <span class="text-muted small fw-semibold text-uppercase">Failed / Cancelled</span>
                <div class="fs-4 fw-bold text-danger mt-1"><?= number_format($stats['failed_count']) ?></div>
                <small class="text-muted">Declined by telco/card</small>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom p-3">
            <form method="GET" action="<?= url('/admin/payments') ?>" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="<?= e($search ?? '') ?>" class="form-control" placeholder="Search reference, customer, or charge ID...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="all" <?= ($status ?? 'all') === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="success" <?= ($status ?? '') === 'success' ? 'selected' : '' ?>>Success / Paid Only</option>
                        <option value="pending" <?= ($status ?? '') === 'pending' ? 'selected' : '' ?>>Pending Only</option>
                        <option value="failed" <?= ($status ?? '') === 'failed' ? 'selected' : '' ?>>Failed Only</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-filter me-1"></i>Filter</button>
                    <?php if (!empty($search) || ($status ?? 'all') !== 'all'): ?>
                        <a href="<?= url('/admin/payments') ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Payments Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3">Tx Ref / Date</th>
                        <th>Customer</th>
                        <th>Purpose</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Channel</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">No online payments found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark font-monospace"><?= e($p['tx_ref']) ?></div>
                                    <small class="text-muted"><?= date('d-M-Y H:i', strtotime($p['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="text-dark fw-semibold"><?= e($p['customer_name'] ?: 'Walk-in / Guest') ?></div>
                                    <?php if (!empty($p['customer_phone'])): ?>
                                        <small class="text-muted"><?= e($p['customer_phone']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= strtoupper(str_replace('_', ' ', $p['purpose'])) ?> <?= $p['purpose_id'] ? '#' . $p['purpose_id'] : '' ?>
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-success">
                                    <?= number_format((int)$p['amount']) ?> <small class="text-muted">MWK</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-secondary border"><?= e($p['channel'] ?: 'mobile_money') ?></span>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $badge = match ($p['status']) {
                                        'success' => 'bg-success text-white',
                                        'pending' => 'bg-warning text-dark',
                                        'failed' => 'bg-danger text-white',
                                        default => 'bg-secondary text-white',
                                    };
                                    ?>
                                    <span class="badge <?= $badge ?> text-uppercase"><?= e($p['status']) ?></span>
                                </td>
                                <td class="text-end pe-3">
                                    <?php if ($p['status'] !== 'success'): ?>
                                        <form method="POST" action="<?= url('/admin/payments/reverify') ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="tx_ref" value="<?= e($p['tx_ref']) ?>">
                                            <button type="submit" class="btn btn-outline-primary btn-sm py-0 px-2">
                                                <i class="bi bi-arrow-repeat me-1"></i>Re-verify
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-success small fw-semibold"><i class="bi bi-check2-all me-1"></i>Credited</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-2">
                <span class="text-muted small">Showing <?= count($payments) ?> of <?= number_format($totalRows) ?> transactions</span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= url('/admin/payments?page=' . ($page - 1) . '&status=' . urlencode($status) . '&q=' . urlencode($search)) ?>">Previous</a>
                        </li>
                        <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $totalPages ?></span></li>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= url('/admin/payments?page=' . ($page + 1) . '&status=' . urlencode($status) . '&q=' . urlencode($search)) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

