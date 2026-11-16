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

        <!-- Z-Fold Controls -->
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-white">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark fs-6"><i class="bi bi-people me-1 text-primary"></i>Hatchery Customer Accounts</span>
                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                    <?= count($customers) ?> Clients
                </span>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold shadow-sm" data-zfold-toggle-all="#customerDirectoryContainer">
                <i class="bi bi-chevron-bar-expand me-1"></i>Unfold All
            </button>
        </div>

        <!-- Z-Fold Customer Directory Cards -->
        <div class="p-3 zfold-container" id="customerDirectoryContainer">
            <?php if (empty($customers)): ?>
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                    <h6 class="fw-bold text-dark">No Customers Found</h6>
                    <p class="small text-muted mb-0">No client accounts match your search parameters.</p>
                </div>
            <?php else: ?>
                <?php foreach ($customers as $c): ?>
                    <?php
                    $invoiced = (int) $c['total_invoiced'];
                    $paid = (int) $c['total_paid'];
                    $bal = max(0, (int) $c['current_balance']);
                    $jobs = (int) $c['total_jobs'];
                    $isInternal = !empty($c['is_internal']);
                    ?>
                    <div class="zfold-card mb-2.5 <?= $bal > 0 ? 'border-start border-4 border-danger' : 'border-start border-4 border-success' ?>">
                        <div class="zfold-header" onclick="if (!event.target.closest('a, button, input, select, .no-fold')) this.closest('.zfold-card').classList.toggle('is-unfolded');">
                            <!-- Tier 1: Customer Name & Phone on Left <---> Balance Due & Chevron on Right -->
                            <div class="zfold-tier-top">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="fw-bold text-dark fs-6">
                                        <?= e($c['name']) ?>
                                    </span>
                                    <?php if ($isInternal): ?>
                                        <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 0.65rem;">Farm Owner / Internal</span>
                                    <?php endif; ?>
                                    <?php if (!empty($c['phone'])): ?>
                                        <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.72rem;">
                                            <i class="bi bi-telephone text-success me-1"></i><?= e($c['phone']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($c['email'])): ?>
                                        <span class="badge bg-light text-dark border font-monospace d-none d-sm-inline-block" style="font-size: 0.72rem;">
                                            <i class="bi bi-envelope text-primary me-1"></i><?= e($c['email']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($bal > 0): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold fs-6 px-2.5 py-1">
                                            <?= number_format($bal) ?> <small style="font-size: 0.65rem;">MWK DUE</small>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2.5 py-1" style="font-size: 0.75rem;">
                                            <i class="bi bi-check-circle-fill me-1"></i>Settled / Nil
                                        </span>
                                    <?php endif; ?>
                                    <button type="button" class="zfold-toggle-btn" aria-label="Toggle customer details" onclick="event.stopPropagation(); this.closest('.zfold-card').classList.toggle('is-unfolded');">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Tier 2: Location & Batches on Left <---> Invoiced vs Paid on Right -->
                            <div class="zfold-tier-bottom mt-1">
                                <span class="text-muted small">
                                    <i class="bi bi-geo-alt text-muted me-1"></i><?= e($c['location'] ?? 'Liwonde / Machinga') ?> &bull; <strong><?= number_format($jobs) ?></strong> incubation batches
                                </span>
                                <div class="d-flex align-items-center gap-2 small text-muted font-monospace">
                                    <span>Invoiced: <strong class="text-dark"><?= number_format($invoiced) ?> MWK</strong></span>
                                    <span>&bull;</span>
                                    <span>Paid: <strong class="text-success"><?= number_format($paid) ?> MWK</strong></span>
                                </div>
                            </div>
                        </div>

                        <!-- Z-Fold Body: Financial Standing & Quick Communications -->
                        <div class="zfold-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <div class="p-3 bg-white rounded-3 border h-100">
                                        <span class="text-muted small d-block mb-1 text-uppercase fw-semibold" style="font-size: 0.7rem;">Financial Ledger</span>
                                        <div class="d-flex justify-content-between py-1 border-bottom small">
                                            <span class="text-muted">Total Invoiced:</span>
                                            <strong class="text-dark font-monospace"><?= number_format($invoiced) ?> MWK</strong>
                                        </div>
                                        <div class="d-flex justify-content-between py-1 border-bottom small">
                                            <span class="text-muted">Total Receipts Paid:</span>
                                            <strong class="text-success font-monospace"><?= number_format($paid) ?> MWK</strong>
                                        </div>
                                        <div class="d-flex justify-content-between pt-1 small">
                                            <span class="fw-semibold text-dark">Current Balance Due:</span>
                                            <strong class="font-monospace <?= $bal > 0 ? 'text-danger' : 'text-success' ?>">
                                                <?= number_format($bal) ?> MWK
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="p-3 bg-white rounded-3 border h-100">
                                        <span class="text-muted small d-block mb-1 text-uppercase fw-semibold" style="font-size: 0.7rem;">Direct Communication</span>
                                        <?php if (!empty($c['phone'])): ?>
                                            <div class="d-flex flex-column gap-1.5 mt-2">
                                                <a href="tel:<?= e($c['phone']) ?>" class="btn btn-sm btn-outline-success rounded-pill w-100 text-start px-3" style="font-size: 0.75rem;">
                                                    <i class="bi bi-telephone-outbound me-1"></i>Voice Call (<?= e($c['phone']) ?>)
                                                </a>
                                                <a href="sms:<?= e($c['phone']) ?>" class="btn btn-sm btn-outline-primary rounded-pill w-100 text-start px-3" style="font-size: 0.75rem;">
                                                    <i class="bi bi-chat-text me-1"></i>Direct SMS Message
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($c['email'])): ?>
                                            <div class="mt-1.5">
                                                <a href="mailto:<?= e($c['email']) ?>" class="btn btn-sm btn-outline-info rounded-pill w-100 text-start px-3" style="font-size: 0.75rem;">
                                                    <i class="bi bi-envelope me-1"></i>Email (<?= e($c['email']) ?>)
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (empty($c['phone']) && empty($c['email'])): ?>
                                            <p class="text-muted small font-monospace">No phone or email registered.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="p-3 bg-white rounded-3 border h-100 d-flex flex-column justify-content-between">
                                        <div>
                                            <span class="text-muted small d-block mb-1 text-uppercase fw-semibold" style="font-size: 0.7rem;">Client Record Actions</span>
                                            <small class="text-muted d-block mb-2">View full historical batch intakes, candling logs, and payment receipts.</small>
                                        </div>
                                        <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                                            <a href="<?= url("/customers/{$c['id']}") ?>" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold" style="font-size: 0.75rem;">
                                                Customer Profile & Batches &rarr;
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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

