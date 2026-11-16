<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('/customers') ?>" class="btn btn-sm btn-light border text-muted">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0 text-dark"><?= e($customer['name']) ?></h1>
                <?php if (!empty($customer['is_internal'])): ?>
                    <span class="badge bg-secondary-subtle text-secondary border fs-6">Msantha Internal</span>
                <?php endif; ?>
            </div>
            <p class="text-muted small mb-0 mt-1">
                <i class="bi bi-geo-alt me-1 text-danger"></i><?= e($customer['location'] ?? 'Location Unspecified') ?>
                <?php if (!empty($customer['phone'])): ?>
                    &middot; <i class="bi bi-telephone me-1 text-success"></i><a href="tel:<?= e($customer['phone']) ?>" class="text-decoration-none text-dark fw-semibold"><?= e($customer['phone']) ?></a>
                    <?php if (!empty($customer['sms_opt_in'])): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1" style="font-size: 0.65rem;">SMS On</span>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (!empty($customer['email'])): ?>
                    &middot; <i class="bi bi-envelope me-1 text-primary"></i><a href="mailto:<?= e($customer['email']) ?>" class="text-decoration-none text-dark fw-semibold"><?= e($customer['email']) ?></a>
                    <?php if (!empty($customer['email_opt_in'])): ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-0 px-1" style="font-size: 0.65rem;">Email On</span>
                    <?php endif; ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url("/customers/{$customer['id']}/edit") ?>" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i>Edit Profile
            </a>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#mergeModal">
                <i class="bi bi-intersect me-1"></i>Merge Duplicate
            </button>
            <a href="<?= url("/incubation/receive?customer_id={$customer['id']}") ?>" class="btn btn-success fw-bold">
                <i class="bi bi-plus-circle me-1"></i>Receive Eggs
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
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-primary">
                <span class="text-muted small fw-semibold text-uppercase">Total Batches</span>
                <div class="fs-3 fw-bold text-primary mt-1"><?= count($jobs) ?></div>
                <small class="text-muted"><?= number_format($eggsSet) ?> eggs incubated</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-success">
                <span class="text-muted small fw-semibold text-uppercase">Chicks Hatched</span>
                <div class="fs-3 fw-bold text-success mt-1"><?= number_format($chicks) ?></div>
                <small class="text-muted">Hatch Rate: <strong><?= $avgHatch ?>%</strong></small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-info">
                <span class="text-muted small fw-semibold text-uppercase">Total Invoiced</span>
                <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($totalCost) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted">Paid: <?= number_format($totalPaid) ?> MWK</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 <?= $balance > 0 ? 'border-danger' : 'border-secondary' ?>">
                <span class="text-muted small fw-semibold text-uppercase">Current Balance</span>
                <div class="fs-3 fw-bold <?= $balance > 0 ? 'text-danger' : 'text-success' ?> mt-1">
                    <?= number_format($balance) ?> <small class="fs-6 text-muted">MWK</small>
                </div>
                <small class="text-muted"><?= $balance > 0 ? 'Outstanding balance due' : 'Fully settled' ?></small>
            </div>
        </div>
    </div>

    <!-- Historical Aliases / Legacy Names Badge Cloud -->
    <?php if (!empty($legacyNames)): ?>
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-4">
            <h6 class="fw-bold text-dark mb-2 small text-uppercase">Normalized Historical Aliases (from Spreadsheets & Merges)</h6>
            <div class="d-flex flex-wrap gap-1">
                <?php foreach ($legacyNames as $alias): ?>
                    <span class="badge bg-light text-secondary border"><?= e($alias) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- History Tab: Incubation Jobs -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom p-3">
            <h5 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-clock-history me-2 text-primary"></i>Incubation Job History</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3">Batch Date</th>
                        <th class="text-end">Eggs Set</th>
                        <th class="text-end">Hatched</th>
                        <th class="text-end">Hatch %</th>
                        <th class="text-end">Cost</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jobs)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
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
                                <td class="text-end fw-semibold text-dark"><?= number_format($cost) ?> MWK</td>
                                <td class="text-end text-success fw-semibold"><?= number_format($paid) ?> MWK</td>
                                <td class="text-end fw-bold <?= $bal > 0 ? 'text-danger' : 'text-muted' ?>"><?= number_format($bal) ?> MWK</td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border"><?= ucfirst(e($j['status'])) ?></span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="<?= url("/incubation-jobs/{$j['id']}") ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Merge Duplicates Modal -->
<div class="modal fade" id="mergeModal" tabindex="-1" aria-labelledby="mergeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('/customers/merge') ?>" onsubmit="return confirm('Are you sure you want to merge this duplicate? All history and jobs will be transferred to <?= e($customer['name']) ?>, and the duplicate will be deleted.');">
                <?= csrf_field() ?>
                <input type="hidden" name="target_id" value="<?= $customer['id'] ?>">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="mergeModalLabel">Merge Duplicate Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold">
                        <i class="bi bi-intersect me-1"></i>Perform Merge
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

