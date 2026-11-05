<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 fw-bold mb-0 text-dark">Commercial Sales & Offloads</h1>
                <span class="badge bg-success-subtle text-success border border-success-subtle fs-6">Revenue</span>
            </div>
            <p class="text-muted small mb-0 mt-1">Live birds, table eggs, fertilised eggs, manure, and debtor credit receivables.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/sales/create') ?>" class="btn btn-success fw-bold">
                <i class="bi bi-cart-plus me-1"></i>Record New Sale
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= e($flashSuccess) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($flashError) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- KPI Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-success">
                <span class="text-muted small fw-semibold text-uppercase">Total Sales Invoiced</span>
                <div class="fs-3 fw-bold text-success mt-1"><?= number_format($stats['total_sales_mwk']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted"><?= number_format($stats['count_sales']) ?> transactions</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-primary">
                <span class="text-muted small fw-semibold text-uppercase">Cash & Mobile Collected</span>
                <div class="fs-3 fw-bold text-primary mt-1"><?= number_format($stats['total_paid_mwk']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted">Settled immediately</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-danger">
                <span class="text-muted small fw-semibold text-uppercase">Debtors (Credit Sales)</span>
                <div class="fs-3 fw-bold text-danger mt-1"><?= number_format($stats['total_debtors_mwk']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted">Customer credit balance</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-info">
                <span class="text-muted small fw-semibold text-uppercase">Average Sale Size</span>
                <div class="fs-3 fw-bold text-dark mt-1">
                    <?= $stats['count_sales'] > 0 ? number_format((int)round($stats['total_sales_mwk'] / $stats['count_sales'])) : 0 ?> <small class="fs-6 text-muted">MWK</small>
                </div>
                <small class="text-muted">Per commercial sale</small>
            </div>
        </div>
    </div>

    <!-- Filter & Table Card -->
    <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
        <div class="card-header bg-white border-bottom p-3">
            <form method="GET" action="<?= url('/sales') ?>" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="<?= e($search ?? '') ?>" class="form-control" placeholder="Search customer, method, or legacy ref...">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-filter me-1"></i>Filter</button>
                    <?php if (!empty($search)): ?>
                        <a href="<?= url('/sales') ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Table View -->
        <div class="table-responsive">
            <table class="table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3 py-3" style="width: 130px;">Sale Date</th>
                        <th class="py-3">Customer Name</th>
                        <th class="py-3" style="width: 140px;">Payment Channel</th>
                        <th class="text-end py-3" style="width: 100px;">Items</th>
                        <th class="text-end py-3" style="width: 140px;">Total Invoiced</th>
                        <th class="text-end py-3" style="width: 140px;">Amount Paid</th>
                        <th class="text-end py-3" style="width: 140px;">Balance</th>
                        <th class="text-end pe-3 py-3" style="width: 110px;">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                No commercial sales recorded matching criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sales as $s): ?>
                            <?php
                            $tot = (int) $s['total'];
                            $pd = (int) $s['paid'];
                            $bal = max(0, $tot - $pd);
                            $isCredit = ($s['payment_method'] === 'credit');
                            ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-dark">
                                    <?= date('d-M-Y', strtotime($s['sale_date'])) ?>
                                </td>
                                <td>
                                    <?php if (!empty($s['customer_name'])): ?>
                                        <div class="fw-bold text-dark"><?= e($s['customer_name']) ?></div>
                                        <small class="text-muted"><?= e($s['customer_phone'] ?? '') ?></small>
                                    <?php else: ?>
                                        <span class="text-muted font-italic">Walk-in / Cash Customer</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isCredit): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">CREDIT (DEBTOR)</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border"><?= strtoupper(str_replace('_', ' ', $s['payment_method'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-semibold text-muted">
                                    <?= number_format((int)$s['items_count']) ?> items
                                </td>
                                <td class="text-end fw-bold text-dark fs-6">
                                    <?= number_format($tot) ?> MWK
                                </td>
                                <td class="text-end fw-semibold text-success">
                                    <?= number_format($pd) ?> MWK
                                </td>
                                <td class="text-end fw-bold <?= $bal > 0 ? 'text-danger' : 'text-muted' ?>">
                                    <?= number_format($bal) ?> MWK
                                </td>
                                <td class="text-end pe-3">
                                    <a href="<?= url("/sales/{$s['id']}/receipt") ?>" target="_blank" class="btn btn-sm btn-outline-dark">
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
                    Page <?= $page ?> of <?= $totalPages ?> (<?= number_format($totalRows) ?> sales)
                </span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?= url('/sales?page=' . ($page - 1) . '&q=' . urlencode($search)) ?>">Previous</a></li>
                        <?php endif; ?>
                        <li class="page-item active"><span class="page-link"><?= $page ?></span></li>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="<?= url('/sales?page=' . ($page + 1) . '&q=' . urlencode($search)) ?>">Next</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

