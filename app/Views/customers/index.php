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
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-info">
                <span class="text-muted small fw-semibold text-uppercase">Total Customers</span>
                <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($stats['total_customers']) ?></div>
                <small class="text-muted">Registered in database</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-success">
                <span class="text-muted small fw-semibold text-uppercase">Total Invoiced</span>
                <div class="fs-3 fw-bold text-success mt-1"><?= number_format($stats['total_spent']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted">Historical Hatchery Fees</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-danger">
                <span class="text-muted small fw-semibold text-uppercase">Uncollected Balances</span>
                <div class="fs-3 fw-bold text-danger mt-1"><?= number_format($stats['total_balance']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted">Customer debts</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-secondary">
                <span class="text-muted small fw-semibold text-uppercase">Farm Internal</span>
                <div class="fs-3 fw-bold text-secondary mt-1"><?= number_format($stats['internal_customers']) ?></div>
                <small class="text-muted">Msantha Farm Accounts</small>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
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

        <!-- Desktop Table View -->
        <div class="table-responsive d-none d-md-block">
            <table class="table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3 py-3" style="width: 220px;">Customer Name</th>
                        <th class="py-3" style="width: 150px;">Phone Number</th>
                        <th class="py-3">Location</th>
                        <th class="text-end py-3" style="width: 100px;">Batches</th>
                        <th class="text-end py-3" style="width: 140px;">Total Invoiced</th>
                        <th class="text-end py-3" style="width: 140px;">Amount Paid</th>
                        <th class="text-end pe-3 py-3" style="width: 140px;">Balance Due</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                No customers found matching criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $c): ?>
                            <?php
                            $invoiced = (int) $c['total_invoiced'];
                            $paid = (int) $c['total_paid'];
                            $bal = max(0, (int) $c['current_balance']);
                            $jobs = (int) $c['total_jobs'];
                            ?>
                            <tr>
                                <td class="ps-3">
                                    <a href="<?= url("/customers/{$c['id']}") ?>" class="fw-bold text-dark text-decoration-none fs-6">
                                        <?= e($c['name']) ?> <i class="bi bi-chevron-right small text-muted"></i>
                                    </a>
                                    <?php if (!empty($c['is_internal'])): ?>
                                        <span class="badge bg-secondary-subtle text-secondary border ms-1" style="font-size: 0.68rem;">Internal</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($c['phone'])): ?>
                                        <a href="tel:<?= e($c['phone']) ?>" class="text-decoration-none text-dark fw-semibold">
                                            <i class="bi bi-telephone text-success me-1"></i><?= e($c['phone']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-muted border"><?= e($c['location'] ?? '—') ?></span>
                                </td>
                                <td class="text-end fw-semibold text-dark">
                                    <?= number_format($jobs) ?>
                                </td>
                                <td class="text-end fw-semibold text-dark">
                                    <?= number_format($invoiced) ?> <small class="text-muted">MWK</small>
                                </td>
                                <td class="text-end text-success fw-semibold">
                                    <?= number_format($paid) ?> <small class="text-muted">MWK</small>
                                </td>
                                <td class="text-end pe-3">
                                    <span class="fw-bold <?= $bal > 0 ? 'text-danger' : 'text-muted' ?>">
                                        <?= number_format($bal) ?> <small>MWK</small>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View (< 768px) -->
        <div class="d-md-none p-3">
            <?php if (empty($customers)): ?>
                <div class="text-center py-4 text-muted">No customers found.</div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($customers as $c): ?>
                        <?php
                        $invoiced = (int) $c['total_invoiced'];
                        $paid = (int) $c['total_paid'];
                        $bal = max(0, (int) $c['current_balance']);
                        ?>
                        <div class="card border rounded-3 p-3 shadow-sm bg-white">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <a href="<?= url("/customers/{$c['id']}") ?>" class="fw-bold text-dark text-decoration-none fs-6">
                                        <?= e($c['name']) ?> <i class="bi bi-chevron-right small text-muted"></i>
                                    </a>
                                    <small class="text-muted d-block"><?= e($c['location'] ?? 'Malawi') ?></small>
                                </div>
                                <?php if (!empty($c['is_internal'])): ?>
                                    <span class="badge bg-secondary-subtle text-secondary border">Internal</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($c['phone'])): ?>
                                <div class="mb-2">
                                    <a href="tel:<?= e($c['phone']) ?>" class="btn btn-sm btn-outline-success w-100">
                                        <i class="bi bi-telephone me-1"></i><?= e($c['phone']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between small text-muted pt-2 border-top">
                                <span>Batches: <strong><?= number_format((int)$c['total_jobs']) ?></strong></span>
                                <span>Balance: <strong class="<?= $bal > 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($bal) ?> MWK</strong></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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

