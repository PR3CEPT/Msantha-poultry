<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('/customers') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i>Customers
                </a>
                <h1 class="h3 fw-bold mb-0 text-dark"><?= e($customer['name']) ?></h1>
                <?php if (!empty($customer['is_internal'])): ?>
                    <span class="badge bg-secondary-subtle text-secondary border fs-6">Msantha Internal</span>
                <?php endif; ?>
            </div>
            <p class="text-muted small mb-0 mt-1">
                <i class="bi bi-geo-alt me-1 text-danger"></i><?= e($customer['location'] ?? 'Location Unspecified') ?>
                <?php if (!empty($customer['phone'])): ?>
                    &middot; <i class="bi bi-telephone me-1 text-success"></i><?= e($customer['phone']) ?>
                <?php endif; ?>
                <?php if (!empty($customer['email'])): ?>
                    &middot; <i class="bi bi-envelope me-1 text-primary"></i><?= e($customer['email']) ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= url("/incubation/receive?customer_id={$customer['id']}") ?>" class="btn btn-success fw-bold">
                <i class="bi bi-plus-circle me-1"></i>Receive Eggs
            </a>
            <a href="<?= url("/customers/{$customer['id']}/edit") ?>" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i>Edit Profile
            </a>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#mergeModal">
                <i class="bi bi-intersect me-1"></i>Merge Duplicate
            </button>
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
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary">
                <span class="text-muted small fw-semibold text-uppercase">Incubation Batches</span>
                <div class="fs-3 fw-bold text-primary mt-1 font-monospace"><?= number_format($totalJobs) ?></div>
                <small class="text-muted font-monospace"><?= number_format($eggsSet) ?> eggs incubated</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                <span class="text-muted small fw-semibold text-uppercase">Chicks Hatched</span>
                <div class="fs-3 fw-bold text-success mt-1 font-monospace"><?= number_format($chicks) ?></div>
                <small class="text-muted">Avg Hatch: <strong><?= $avgHatch ?>%</strong></small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-info">
                <span class="text-muted small fw-semibold text-uppercase">Total Invoiced</span>
                <div class="fs-3 fw-bold text-dark mt-1 font-monospace"><?= number_format($totalCost) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted font-monospace">Paid: <?= number_format($totalPaid) ?> MWK</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 <?= $balance > 0 ? 'border-danger' : 'border-success' ?>">
                <span class="text-muted small fw-semibold text-uppercase">Current Balance</span>
                <div class="fs-3 fw-bold <?= $balance > 0 ? 'text-danger' : 'text-success' ?> mt-1 font-monospace">
                    <?= number_format($balance) ?> <small class="fs-6 text-muted">MWK</small>
                </div>
                <small class="text-muted"><?= $balance > 0 ? 'Outstanding balance due' : 'Fully settled / Nil' ?></small>
            </div>
        </div>
    </div>

    <!-- Balanced Two-Column Layout -->
    <div class="row g-4 mb-4">
        <!-- Left Column: Customer Profile Card & Metadata -->
        <div class="col-12 col-lg-4">
            <!-- Profile Details Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                <div class="card-header bg-white border-bottom p-3">
                    <h5 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-person-badge me-2 text-primary"></i>Client Information</h5>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <small class="text-muted text-uppercase d-block" style="font-size: 0.7rem;">Customer Full Name</small>
                        <div class="fw-bold text-dark fs-6"><?= e($customer['name']) ?></div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted text-uppercase d-block" style="font-size: 0.7rem;">Mobile Phone</small>
                        <?php if (!empty($customer['phone'])): ?>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <a href="tel:<?= e($customer['phone']) ?>" class="fw-semibold text-dark text-decoration-none font-monospace">
                                    <i class="bi bi-telephone text-success me-1"></i><?= e($customer['phone']) ?>
                                </a>
                                <?php if (!empty($customer['sms_opt_in'])): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 0.65rem;">SMS Enabled</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border rounded-pill" style="font-size: 0.65rem;">SMS Off</span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted small">Not provided</span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted text-uppercase d-block" style="font-size: 0.7rem;">Email Address</small>
                        <?php if (!empty($customer['email'])): ?>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <a href="mailto:<?= e($customer['email']) ?>" class="small text-primary text-decoration-none font-monospace">
                                    <i class="bi bi-envelope me-1"></i><?= e($customer['email']) ?>
                                </a>
                                <?php if (!empty($customer['email_opt_in'])): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill" style="font-size: 0.65rem;">Opted In</span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted small">No email registered</span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted text-uppercase d-block" style="font-size: 0.7rem;">Location</small>
                        <div class="small text-dark mt-1">
                            <i class="bi bi-geo-alt text-danger me-1"></i><?= e($customer['location'] ?? 'Liwonde, Malawi') ?>
                        </div>
                    </div>

                    <?php if (!empty($customer['notes'])): ?>
                        <div class="mb-3">
                            <small class="text-muted text-uppercase d-block" style="font-size: 0.7rem;">Client Notes</small>
                            <p class="small text-muted mb-0 mt-1"><?= nl2br(e($customer['notes'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                        <small class="text-muted">Account Registered</small>
                        <small class="fw-semibold text-dark font-monospace"><?= date('d-M-Y', strtotime($customer['created_at'] ?? 'now')) ?></small>
                    </div>
                </div>
            </div>

            <!-- Normalized Historical Aliases Card -->
            <?php if (!empty($legacyNames)): ?>
                <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                    <div class="card-header bg-white border-bottom p-3">
                        <h6 class="fw-bold mb-0 text-dark small text-uppercase"><i class="bi bi-tags me-2 text-secondary"></i>Historical Aliases</h6>
                    </div>
                    <div class="card-body p-3">
                        <small class="text-muted d-block mb-2">Imported names and merged aliases linked to this client:</small>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach ($legacyNames as $alias): ?>
                                <span class="badge bg-light text-secondary border"><?= e($alias) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quick Action Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <h6 class="fw-bold text-dark mb-3 small text-uppercase">Account Operations</h6>
                <div class="d-grid gap-2">
                    <a href="<?= url("/incubation/receive?customer_id={$customer['id']}") ?>" class="btn btn-outline-success btn-sm text-start">
                        <i class="bi bi-egg-fried me-2"></i>Receive Incubation Batch
                    </a>
                    <a href="<?= url("/sales/create?customer_id={$customer['id']}") ?>" class="btn btn-outline-primary btn-sm text-start">
                        <i class="bi bi-cart-plus me-2"></i>Create Commercial Sale
                    </a>
                    <a href="<?= url("/customers/{$customer['id']}/edit") ?>" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="bi bi-pencil-square me-2"></i>Update Contact Information
                    </a>
                </div>
            </div>
        </div>

        <!-- Right Column: Incubation Batches & Commercial Sales -->
        <div class="col-12 col-lg-8">
            <!-- Incubation Job History Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-clock-history me-2 text-primary"></i>Incubation Job History</h5>
                    <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                        Showing <?= count($jobs) ?> of <?= number_format($totalJobs) ?> Batches
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 font-monospace">
                        <thead class="table-light text-muted small text-uppercase" style="font-size: 0.72rem;">
                            <tr>
                                <th class="ps-3 py-3" style="font-family: inherit;">Batch Date</th>
                                <th class="text-end py-3">Eggs Set</th>
                                <th class="text-end py-3">Hatched</th>
                                <th class="text-end py-3">Hatch %</th>
                                <th class="text-end py-3">Cost</th>
                                <th class="text-end py-3">Paid</th>
                                <th class="text-end py-3">Balance</th>
                                <th class="text-center py-3" style="font-family: inherit;">Status</th>
                                <th class="text-end pe-3 py-3" style="font-family: inherit;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($jobs)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted font-sans-serif">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                        No incubation jobs recorded for this customer yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($jobs as $j): ?>
                                    <?php
                                    $eggs = (int) $j['eggs_incubated'];
                                    $hatched = (int) $j['hatched'];
                                    $hatchPct = !empty($j['hatch_percent']) ? (float)$j['hatch_percent'] : ($eggs > 0 ? round(($hatched / $eggs) * 100, 1) : 0);
                                    $cost = (int) $j['cost'];
                                    $paid = (int) $j['total_paid'];
                                    $bal = (int) $j['balance'];
                                    ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-semibold text-dark font-monospace small"><?= !empty($j['date_received']) ? date('d-M-Y', strtotime($j['date_received'])) : '—' ?></div>
                                        </td>
                                        <td class="text-end fw-bold text-dark"><?= number_format($eggs) ?></td>
                                        <td class="text-end fw-semibold text-success"><?= number_format($hatched) ?></td>
                                        <td class="text-end">
                                            <?php if ($hatchPct > 0): ?>
                                                <span class="badge <?= $hatchPct >= 65 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border' ?>"><?= $hatchPct ?>%</span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-semibold text-dark"><?= number_format($cost) ?></td>
                                        <td class="text-end text-success fw-semibold"><?= number_format($paid) ?></td>
                                        <td class="text-end fw-bold <?= $bal > 0 ? 'text-danger' : 'text-muted' ?>"><?= number_format($bal) ?></td>
                                        <td class="text-center font-sans-serif">
                                            <span class="badge bg-light text-dark border"><?= ucfirst(e($j['status'])) ?></span>
                                        </td>
                                        <td class="text-end pe-3 font-sans-serif">
                                            <a href="<?= url("/incubation-jobs/{$j['id']}") ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-0.5" style="font-size: 0.72rem;">
                                                View &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for Incubation Batches -->
                <?php if (!empty($totalPages) && $totalPages > 1): ?>
                    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center p-3">
                        <span class="text-muted small">
                            Showing page <?= $page ?> of <?= $totalPages ?> (<?= number_format($totalJobs) ?> total batches)
                        </span>
                        <nav aria-label="Customer jobs pagination">
                            <ul class="pagination pagination-sm mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= url('/customers/' . $customer['id'] . '?page=' . ($page - 1)) ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item active">
                                    <span class="page-link"><?= $page ?></span>
                                </li>
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= url('/customers/' . $customer['id'] . '?page=' . ($page + 1)) ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Commercial Sales Section (if any) -->
            <?php if (!empty($sales)): ?>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                    <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-cart-check me-2 text-success"></i>Commercial Purchases & Offloads</h5>
                        <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                            <?= count($sales) ?> Transactions
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 font-monospace">
                            <thead class="table-light text-muted small text-uppercase" style="font-size: 0.72rem;">
                                <tr>
                                    <th class="ps-3 py-3" style="font-family: inherit;">Sale Date</th>
                                    <th class="py-3" style="font-family: inherit;">Channel</th>
                                    <th class="text-end py-3">Items</th>
                                    <th class="text-end py-3">Invoiced</th>
                                    <th class="text-end py-3">Paid</th>
                                    <th class="text-end py-3">Balance</th>
                                    <th class="text-end pe-3 py-3" style="font-family: inherit;">Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales as $s): ?>
                                    <?php
                                    $sTot = (int) $s['total'];
                                    $sPaid = (int) $s['paid'];
                                    $sBal = max(0, $sTot - $sPaid);
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold text-dark"><?= date('d-M-Y', strtotime($s['sale_date'])) ?></td>
                                        <td><span class="badge bg-light text-dark border font-sans-serif"><?= strtoupper(str_replace('_', ' ', $s['payment_method'])) ?></span></td>
                                        <td class="text-end text-muted"><?= number_format((int)$s['items_count']) ?></td>
                                        <td class="text-end fw-bold text-dark"><?= number_format($sTot) ?> MWK</td>
                                        <td class="text-end text-success fw-semibold"><?= number_format($sPaid) ?> MWK</td>
                                        <td class="text-end fw-bold <?= $sBal > 0 ? 'text-danger' : 'text-muted' ?>"><?= number_format($sBal) ?> MWK</td>
                                        <td class="text-end pe-3 font-sans-serif">
                                            <a href="<?= url("/sales/{$s['id']}/receipt") ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-2 py-0.5" style="font-size: 0.7rem;">
                                                Receipt
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Merge Duplicates Modal -->
<div class="modal fade" id="mergeModal" tabindex="-1" aria-labelledby="mergeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" action="<?= url('/customers/merge') ?>" onsubmit="return confirm('Are you sure you want to merge this duplicate? All history and jobs will be transferred to <?= e($customer['name']) ?>, and the duplicate will be deleted.');">
                <?= csrf_field() ?>
                <input type="hidden" name="target_id" value="<?= $customer['id'] ?>">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="mergeModalLabel"><i class="bi bi-intersect me-2 text-danger"></i>Merge Duplicate Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Select a duplicate customer to merge <strong>INTO</strong> <strong><?= e($customer['name']) ?></strong>.
                        All incubation batches, payments, and SMS records will be transferred, and the duplicate's name/phone will be recorded in <code>legacy_names</code>.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Duplicate Customer to Absorb</label>
                        <select name="source_id" class="form-select" required>
                            <option value="">-- Choose duplicate customer --</option>
                            <?php foreach ($potentialDuplicates as $other): ?>
                                <option value="<?= $other['id'] ?>">
                                    <?= e($other['name']) ?> (<?= e($other['phone'] ?? 'No phone') ?>, <?= e($other['location'] ?? 'No location') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold">
                        <i class="bi bi-intersect me-1"></i>Perform Merge
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
