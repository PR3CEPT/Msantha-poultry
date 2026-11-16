<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 fw-bold mb-0 text-dark">Customer Directory</h1>
                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle fs-6">
                    <?= number_format($stats['total_customers']) ?> Accounts
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Hatchery clients, contact phones, normalized historical aliases, job history, and billing balances.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/customers/create') ?>" class="btn btn-success fw-bold">
                <i class="bi bi-person-plus me-1"></i>Register Customer
            </a>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-info">
                <span class="text-muted small fw-semibold text-uppercase">Total Customers</span>
                <div class="fs-3 fw-bold text-dark mt-1 font-monospace"><?= number_format($stats['total_customers']) ?></div>
                <small class="text-muted">Registered in database</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                <span class="text-muted small fw-semibold text-uppercase">Total Invoiced</span>
                <div class="fs-3 fw-bold text-success mt-1 font-monospace"><?= number_format($stats['total_spent']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted">Historical Hatchery Fees</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-danger">
                <span class="text-muted small fw-semibold text-uppercase">Uncollected Balances</span>
                <div class="fs-3 fw-bold text-danger mt-1 font-monospace"><?= number_format($stats['total_balance']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted">Customer debts</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-secondary">
                <span class="text-muted small fw-semibold text-uppercase">Farm Internal</span>
                <div class="fs-3 fw-bold text-secondary mt-1 font-monospace"><?= number_format($stats['internal_customers']) ?></div>
                <small class="text-muted">Msantha Farm Accounts</small>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
        <div class="card-header bg-white border-bottom p-3">
            <form method="GET" action="<?= url('/customers') ?>" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="<?= e($search ?? '') ?>" class="form-control" placeholder="Search customer name, phone (+265...), or location...">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-filter me-1"></i>Filter</button>
                    <?php if (!empty($search)): ?>
                        <a href="<?= url('/customers') ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark fs-6"><i class="bi bi-people me-1 text-primary"></i>Customer Client Directory</span>
            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                Showing <?= count($customers) ?> of <?= number_format($totalRows) ?>
            </span>
        </div>

        <!-- Customer Directory Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase font-monospace" style="font-size: 0.72rem;">
                    <tr>
                        <th class="ps-3 py-3">Customer Name</th>
                        <th class="py-3">Contact Details</th>
                        <th class="py-3">Location</th>
                        <th class="text-center py-3" style="width: 100px;">Batches</th>
                        <th class="text-end py-3" style="width: 130px;">Invoiced</th>
                        <th class="text-end py-3" style="width: 130px;">Paid</th>
                        <th class="text-end py-3" style="width: 140px;">Balance Due</th>
                        <th class="text-end pe-3 py-3" style="width: 110px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                No customer accounts found matching your search.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $c): ?>
                            <?php
                            $invoiced = (int) $c['total_invoiced'];
                            $paid = (int) $c['total_paid'];
                            $bal = max(0, (int) $c['current_balance']);
                            $jobs = (int) $c['total_jobs'];
                            $isInternal = !empty($c['is_internal']);
                            ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark fs-6"><?= e($c['name']) ?></div>
                                    <?php if ($isInternal): ?>
                                        <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 0.65rem;">Farm Owner / Internal</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($c['phone'])): ?>
                                        <div class="font-monospace small text-dark">
                                            <i class="bi bi-telephone text-success me-1"></i><?= e($c['phone']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($c['email'])): ?>
                                        <div class="small text-muted font-monospace" style="font-size: 0.72rem;">
                                            <i class="bi bi-envelope text-primary me-1"></i><?= e($c['email']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (empty($c['phone']) && empty($c['email'])): ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <i class="bi bi-geo-alt me-1"></i><?= e($c['location'] ?? 'Liwonde') ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border font-monospace"><?= number_format($jobs) ?></span>
                                </td>
                                <td class="text-end font-monospace text-muted small">
                                    <?= number_format($invoiced) ?> MWK
                                </td>
                                <td class="text-end font-monospace text-success fw-semibold small">
                                    <?= number_format($paid) ?> MWK
                                </td>
                                <td class="text-end font-monospace">
                                    <?php if ($bal > 0): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold fs-6">
                                            <?= number_format($bal) ?> MWK
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            Settled / Nil
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="<?= url("/customers/{$c['id']}") ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-0.5" style="font-size: 0.72rem;">
                                        Profile &rarr;
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
                    Page <?= $page ?> of <?= $totalPages ?> (<?= number_format($totalRows) ?> customers)
                </span>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('/customers?page=' . ($page - 1) . '&q=' . urlencode($search)) ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        <li class="page-item active">
                            <span class="page-link"><?= $page ?></span>
                        </li>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('/customers?page=' . ($page + 1) . '&q=' . urlencode($search)) ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

