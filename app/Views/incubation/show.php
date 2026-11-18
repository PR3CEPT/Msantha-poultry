<?php declare(strict_types=1); ?>
<?php
$isOwnerBatch = !empty($job['customer_is_internal']) || stripos((string)($job['customer_name'] ?? ''), 'msantha') !== false;
?>

<div class="p-3 p-md-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="<?= url('/incubation-jobs') ?>" class="btn btn-sm btn-light border text-muted">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0 text-dark">
                    Batch <?= e($job['batch_code'] ?: ('#' . $job['id'])) ?>
                    <?php if (!empty($job['batch_code'])): ?>
                        <span class="text-muted fs-6 fw-normal">(#<?= $job['id'] ?>)</span>
                    <?php endif; ?>
                </h1>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
                    <?= ucfirst(e($job['status'])) ?>
                </span>
                <?php if ($isOwnerBatch): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle fs-6">
                        <i class="bi bi-shield-check me-1"></i>Owner / Complimentary
                    </span>
                <?php elseif ((int)$job['balance'] < 0): ?>
                    <span class="badge bg-warning text-dark border border-warning fs-6">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Overpaid
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Customer: <a href="<?= url("/customers/{$job['customer_id']}") ?>" class="fw-bold text-dark text-decoration-none"><?= e($job['customer_name']) ?></a>
                &middot; <i class="bi bi-telephone me-1"></i><?= e($job['customer_phone'] ?? 'No phone') ?>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <?php if (\App\Core\Auth::can('incubation', 'edit')): ?>
                <a href="<?= url("/incubation-jobs/{$job['id']}/edit") ?>" class="btn btn-outline-primary">
                    <i class="bi bi-pencil-square me-1"></i>Edit Batch
                </a>
            <?php endif; ?>
            <a href="<?= url("/incubation-jobs/{$job['id']}/receipt") ?>" target="_blank" class="btn btn-outline-dark">
                <i class="bi bi-receipt me-1"></i>Print Receipt
            </a>
            <?php if (!empty($job['hatched']) && (int)$job['hatched'] > 0): ?>
                <a href="<?= url("/incubation-jobs/{$job['id']}/certificate") ?>" target="_blank" class="btn btn-outline-success">
                    <i class="bi bi-award me-1"></i>Hatch Certificate
                </a>
            <?php endif; ?>
            <?php if (\App\Core\Auth::hasRole(['owner', 'manager'])): ?>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteBatchModal">
                    <i class="bi bi-trash3 me-1"></i>Delete
                </button>
            <?php endif; ?>
            <?php if ($isOwnerBatch): ?>
                <span class="badge bg-light text-muted border p-2">
                    <i class="bi bi-check2-circle text-success me-1"></i>Owner Internal &bull; No Payment Required
                </span>
            <?php else: ?>
                <?php if ((int)$job['balance'] > 0): ?>
                    <form method="POST" action="<?= url('/payments/initiate') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="purpose" value="incubation_job">
                        <input type="hidden" name="purpose_id" value="<?= $job['id'] ?>">
                        <input type="hidden" name="customer_id" value="<?= $job['customer_id'] ?>">
                        <input type="hidden" name="amount" value="<?= (int)$job['balance'] ?>">
                        <button type="submit" class="btn btn-primary fw-bold">
                            <i class="bi bi-credit-card me-1"></i>Pay Online
                        </button>
                    </form>
                    <?php if (!empty($job['customer_phone'])): ?>
                        <form method="POST" action="<?= url('/payments/send-link-sms') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="purpose" value="incubation_job">
                            <input type="hidden" name="purpose_id" value="<?= $job['id'] ?>">
                            <input type="hidden" name="customer_id" value="<?= $job['customer_id'] ?>">
                            <input type="hidden" name="amount" value="<?= (int)$job['balance'] ?>">
                            <button type="submit" class="btn btn-outline-info">
                                <i class="bi bi-chat-text me-1"></i>Send Pay Link SMS
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
                <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#paymentModal">
                    <i class="bi bi-cash-stack me-1"></i>Add Payment
                </button>
            <?php endif; ?>
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

    <!-- Batch Lifecycle Stepper -->
    <?php
    $steps = ['received', 'incubating', 'hatching', 'completed', 'collected'];
    $currentStatus = strtolower((string)$job['status']);
    $currentIndex = array_search($currentStatus, $steps, true);
    if ($currentIndex === false) $currentIndex = 1;
    ?>
    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-4">
        <div class="d-flex justify-content-between position-relative">
            <?php foreach ($steps as $idx => $st): ?>
                <div class="text-center flex-grow-1 position-relative" style="z-index: 2;">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm mb-1 fw-bold
                        <?= $idx <= $currentIndex ? 'bg-success text-white' : 'bg-light text-muted border' ?>"
                        style="width: 38px; height: 38px; font-size: 0.85rem;">
                        <?php if ($idx < $currentIndex): ?>
                            <i class="bi bi-check-lg"></i>
                        <?php else: ?>
                            <?= $idx + 1 ?>
                        <?php endif; ?>
                    </div>
                    <div class="small fw-semibold <?= $idx <= $currentIndex ? 'text-dark' : 'text-muted' ?>">
                        <?= ucfirst($st) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-primary">
                <span class="text-muted small fw-semibold text-uppercase">Eggs Set</span>
                <div class="fs-3 fw-bold text-dark mt-1"><?= number_format((int)$job['eggs_incubated']) ?></div>
                <small class="text-muted"><?= number_format((int)$job['eggs_received']) ?> Received</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-success">
                <span class="text-muted small fw-semibold text-uppercase">Chicks Hatched</span>
                <div class="fs-3 fw-bold text-success mt-1"><?= number_format((int)$job['hatched']) ?></div>
                <small class="text-muted">Hatch Rate: <strong><?= !empty($job['hatch_percent']) ? $job['hatch_percent'] . '%' : '—' ?></strong></small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-info">
                <span class="text-muted small fw-semibold text-uppercase">Hatchery Cost</span>
                <div class="fs-3 fw-bold text-dark mt-1"><?= number_format((int)$job['cost']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted">@ <?= number_format((int)$job['rate_per_egg']) ?> MWK / egg</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 <?= $isOwnerBatch ? 'border-success' : ((int)$job['balance'] > 0 ? 'border-danger' : ((int)$job['balance'] < 0 ? 'border-warning' : 'border-success')) ?>">
                <span class="text-muted small fw-semibold text-uppercase">Balance Due</span>
                <?php if ($isOwnerBatch): ?>
                    <div class="fs-4 fw-bold text-success mt-1">0 MWK</div>
                    <small class="text-muted">Owner Complimentary Batch</small>
                <?php else: ?>
                    <div class="fs-3 fw-bold <?= (int)$job['balance'] > 0 ? 'text-danger' : ((int)$job['balance'] < 0 ? 'text-warning' : 'text-success') ?> mt-1">
                        <?= number_format((int)$job['balance']) ?> <small class="fs-6 text-muted">MWK</small>
                    </div>
                    <small class="text-muted">Paid: <?= number_format((int)$job['total_paid']) ?> MWK</small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Candling & Hatch Results Section -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-header bg-white border-bottom p-3">
                    <h5 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-egg text-warning me-2"></i>Candling & Hatch Results</h5>
                </div>
                <div class="card-body p-3">
                    <form method="POST" action="<?= url("/incubation-jobs/{$job['id']}/hatch") ?>">
                        <?= csrf_field() ?>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-muted">Transfer Date (To Hatcher)</label>
                                <div class="fw-bold text-dark"><?= !empty($job['date_transfer']) ? date('d-M-Y', strtotime($job['date_transfer'])) : '—' ?></div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-muted">Expected Hatch Date</label>
                                <div class="fw-bold text-dark"><?= !empty($job['expected_hatch_date']) ? date('d-M-Y', strtotime($job['expected_hatch_date'])) : '—' ?></div>
                            </div>
                        </div>

                        <hr class="my-3 text-muted">

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Actual Hatch Date</label>
                            <input type="date" name="date_hatched" class="form-control" value="<?= $job['date_hatched'] ?? date('Y-m-d') ?>">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label class="form-label small fw-semibold">Fertile</label>
                                <input type="number" name="fertile" class="form-control" min="0" value="<?= $job['fertile'] ?? '' ?>" placeholder="0">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-semibold">Infertile</label>
                                <input type="number" name="infertile" class="form-control" min="0" value="<?= $job['infertile'] ?? '' ?>" placeholder="0">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-semibold">Chicks Hatched</label>
                                <input type="number" name="hatched" class="form-control" min="0" value="<?= $job['hatched'] ?? '' ?>" placeholder="0">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="bi bi-check2-circle me-1"></i>Save Hatch Results
                        </button>
                    </form>

                    <?php if ($job['status'] === 'collected'): ?>
                        <div class="alert alert-dark d-flex align-items-center py-2 px-3 mt-3 mb-0 small rounded-2">
                            <i class="bi bi-box-seam text-warning fs-5 me-2"></i>
                            <div>
                                <span class="fw-bold d-block text-white">Chicks Collected</span>
                                <span class="text-secondary"><?= e($job['remarks'] ?? 'Handover complete.') ?></span>
                            </div>
                        </div>
                    <?php elseif (!empty($job['hatched']) && (int)$job['hatched'] > 0): ?>
                        <div class="mt-3 pt-3 border-top">
                            <button type="button" class="btn btn-warning w-100 fw-bold shadow-sm text-dark" data-bs-toggle="modal" data-bs-target="#collectChicksModal">
                                <i class="bi bi-box-seam me-1"></i>Notify System: Chicks Collected
                            </button>
                            <small class="text-muted d-block text-center mt-1">Record handover of live chicks to customer</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Payments & 25/15/30/20/10 Allocations Section -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-wallet2 text-success me-2"></i>Payments & Allocations (25/15/30/20/10)</h5>
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
                        <i class="bi bi-plus-lg me-1"></i>Add
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem;">
                                <tr>
                                    <th class="ps-3">Date</th>
                                    <th>Method</th>
                                    <th class="text-end">Paid (MWK)</th>
                                    <th class="text-end">Elec (25%)</th>
                                    <th class="text-end">Water (15%)</th>
                                    <th class="text-end">Labour (30%)</th>
                                    <th class="text-end">Tel (20%)</th>
                                    <th class="text-end pe-3">Profit (10%+)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No payments recorded yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payments as $p): ?>
                                        <?php
                                        $pAllocs = $allocationsByPayment[(int)$p['id']] ?? [];
                                        $allocMap = [];
                                        foreach ($pAllocs as $pa) {
                                            $allocMap[$pa['category']] = (int)$pa['amount'];
                                        }
                                        ?>
                                        <tr>
                                            <td class="ps-3 fw-semibold"><?= date('d-M-Y', strtotime($p['paid_on'])) ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= strtoupper(str_replace('_', ' ', $p['method'])) ?></span></td>
                                            <td class="text-end fw-bold text-success"><?= number_format((int)$p['amount']) ?></td>
                                            <td class="text-end text-muted"><?= number_format($allocMap['electricity'] ?? 0) ?></td>
                                            <td class="text-end text-muted"><?= number_format($allocMap['water'] ?? 0) ?></td>
                                            <td class="text-end text-muted"><?= number_format($allocMap['labour'] ?? 0) ?></td>
                                            <td class="text-end text-muted"><?= number_format($allocMap['telephone'] ?? 0) ?></td>
                                            <td class="text-end pe-3 fw-semibold text-primary"><?= number_format($allocMap['profit'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url("/incubation-jobs/{$job['id']}/payment") ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="paymentModalLabel">Record Customer Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small">
                        Payments are automatically split: <strong>25% electricity</strong>, <strong>15% water</strong>, <strong>30% labour</strong>, <strong>20% telephone</strong>, <strong>10% profit</strong> (remainder to profit).
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Date</label>
                        <input type="date" name="paid_on" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount to Pay (MWK)</label>
                        <input type="number" name="amount" class="form-control fs-5 fw-bold" min="1" value="<?= max(0, (int)$job['balance']) ?>" required>
                        <small class="text-muted">Remaining Balance: <?= number_format((int)$job['balance']) ?> MWK</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Channel</label>
                        <select name="method" class="form-select" required>
                            <option value="cash">Cash Payment</option>
                            <option value="airtel_money">Airtel Money</option>
                            <option value="tnm_mpamba">TNM Mpamba</option>
                            <option value="bank">National Bank / Standard Bank Transfer</option>
                            <option value="paychangu">PayChangu Online</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">
                        <i class="bi bi-check2-circle me-1"></i>Record & Allocate Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Notify System: Chicks Collected Modal -->
<div class="modal fade" id="collectChicksModal" tabindex="-1" aria-labelledby="collectChicksModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="collectChicksModalLabel">
                    <i class="bi bi-box-seam me-2"></i>Record Chicks Handover & Collection
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url("/incubation-jobs/{$job['id']}/collect") ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="alert alert-light border small mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Batch:</span>
                            <strong><?= e($job['batch_code'] ?: ('#' . $job['id'])) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Customer:</span>
                            <strong><?= e($job['customer_name']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Recorded Hatched Chicks:</span>
                            <strong class="text-success"><?= number_format((int)$job['hatched']) ?> chicks (<?= $job['hatch_percent'] ?? '0' ?>% hatch rate)</strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date of Collection <span class="text-danger">*</span></label>
                        <input type="date" name="date_collected" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Live Chicks Collected / Handed Over <span class="text-danger">*</span></label>
                        <input type="number" name="chicks_collected" class="form-control fs-5 fw-bold text-success" min="0" max="<?= max(1, (int)$job['hatched'] * 2) ?>" value="<?= (int)$job['hatched'] ?>" required>
                        <small class="text-muted">Enter the actual count taken by the customer (in case of dead-in-shell or recount).</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Collector Name / Handover Remarks</label>
                        <input type="text" name="collector_notes" class="form-control" placeholder="e.g. Picked up by owner, all lively and vaccinated">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning fw-bold text-dark">
                        <i class="bi bi-check2-circle me-1"></i>Confirm Collection & Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (\App\Core\Auth::hasRole(['owner', 'manager'])): ?>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteBatchModal" tabindex="-1" aria-labelledby="deleteBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteBatchModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Incubation Batch
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-3">
                    Are you sure you want to permanently delete batch <strong><?= e($job['batch_code'] ?: ('#' . $job['id'])) ?></strong> for <strong><?= e($job['customer_name']) ?></strong>?
                </p>
                <div class="alert alert-warning small mb-3">
                    <i class="bi bi-shield-lock me-1"></i>
                    <strong>Accounting Protection:</strong> Batches with recorded cash or mobile money payments cannot be deleted to protect financial reconciliations. Any unpaid or accidental test entry will be wiped cleanly.
                </div>
                <p class="text-danger small mb-0">This operation is irreversible and will be logged in the system audit trail.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="<?= url("/incubation-jobs/{$job['id']}/delete") ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger fw-bold">
                        <i class="bi bi-trash3 me-1"></i>Yes, Delete Batch
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

