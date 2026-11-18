<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-primary-subtle text-primary fw-bold me-2 fs-6">
                                <?= e($job['batch_code'] ?: ('#' . $job['id'])) ?>
                            </span>
                            <h1 class="h5 fw-bold d-inline-block mb-0 text-dark">
                                Edit Incubation Batch Details
                            </h1>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?= url("/incubation-jobs/{$job['id']}") ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Back to Batch
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <?php if (!empty($flashError)): ?>
                        <div class="alert alert-danger mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($flashError) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= url("/incubation-jobs/{$job['id']}") ?>" id="editBatchForm">
                        <?= csrf_field() ?>

                        <?php if (in_array(strtolower((string)$job['status']), ['completed', 'collected'], true)): ?>
                            <div class="alert alert-warning small mb-4">
                                <i class="bi bi-shield-exclamation me-1"></i>
                                <strong>Post-Hatch Integrity Notice:</strong> This batch has reached <em><?= ucfirst(e($job['status'])) ?></em>. Eggs set and pricing rates should generally remain locked to preserve financial ledger consistency and historical hatch rates. Only adjust candling/hatch recounts, collection notes, or minor customer details.
                            </div>
                        <?php endif; ?>

                        <!-- 1. Customer Selection -->
                        <div class="card bg-light border p-3 rounded-3 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-person me-2 text-primary"></i>1. Customer Information</h6>
                                <span class="badge bg-secondary-subtle text-secondary small">Customer ID: <?= (int)$job['customer_id'] ?></span>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                                <select name="customer_id" id="customerSelect" class="form-select" required>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= ((int)$job['customer_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                                            <?= e($c['name']) ?> (<?= e($c['phone'] ?? 'No phone') ?><?= !empty($c['location']) ? ', ' . e($c['location']) : '' ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">
                                    Changing customer will automatically synchronize the batch code prefix to the new customer's initials.
                                </small>
                            </div>
                        </div>

                        <!-- 2. Species, Breed & Incubator -->
                        <div class="card bg-light border p-3 rounded-3 mb-4">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-egg-fried me-2 text-primary"></i>2. Species, Breed & Machine</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Species <span class="text-danger">*</span></label>
                                    <select name="species_id" id="speciesSelect" class="form-select" required>
                                        <?php foreach ($species as $s): ?>
                                            <option value="<?= $s['id'] ?>" data-days="<?= $s['incubation_days'] ?>" <?= ((int)($job['species_id'] ?? 1) === (int)$s['id']) ? 'selected' : '' ?>>
                                                <?= e($s['name']) ?> (<?= $s['incubation_days'] ?> Days total)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small id="speciesHelp" class="text-muted d-block mt-1"></small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Breed (Optional)</label>
                                    <select name="breed_id" class="form-select">
                                        <option value="">-- Unspecified / Mixed --</option>
                                        <?php foreach ($breeds as $b): ?>
                                            <option value="<?= $b['id'] ?>" <?= ((int)($job['breed_id'] ?? 0) === (int)$b['id']) ? 'selected' : '' ?>>
                                                <?= e($b['name']) ?> (<?= e($b['species_name']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Incubator Unit</label>
                                    <select name="incubator_id" class="form-select">
                                        <option value="">-- Unassigned --</option>
                                        <?php foreach ($incubators as $inc): ?>
                                            <option value="<?= $inc['id'] ?>" <?= ((int)($job['incubator_id'] ?? 0) === (int)$inc['id']) ? 'selected' : '' ?>>
                                                <?= e($inc['name']) ?> (Cap: <?= (int)$inc['capacity'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Quantities & Hatchery Fees -->
                        <div class="card bg-light border p-3 rounded-3 mb-4">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-cash-stack me-2 text-primary"></i>3. Egg Quantities & Hatchery Fees</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Eggs Received <span class="text-danger">*</span></label>
                                    <input type="number" name="eggs_received" id="eggsReceived" class="form-control" required min="0" value="<?= (int)($job['eggs_received'] ?? 0) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Eggs Incubated (Set) <span class="text-danger">*</span></label>
                                    <input type="number" name="eggs_incubated" id="eggsIncubated" class="form-control" required min="0" value="<?= (int)($job['eggs_incubated'] ?? 0) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Rate Per Egg (MWK) <span class="text-danger">*</span></label>
                                    <input type="number" name="rate_per_egg" id="ratePerEgg" class="form-control" required min="0" value="<?= (int)($job['rate_per_egg'] ?? 120) ?>">
                                </div>
                                <div class="col-12">
                                    <div class="p-3 bg-white rounded-3 border d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="fw-semibold text-muted d-block">Batch Total Cost:</span>
                                            <small class="text-muted">Recorded Paid: <strong><?= number_format((int)($job['total_paid'] ?? 0)) ?> MWK</strong> &middot; Current Balance: <strong><?= number_format((int)($job['balance'] ?? 0)) ?> MWK</strong></small>
                                        </div>
                                        <span class="fs-4 fw-bold text-primary" id="costDisplay"><?= number_format((int)($job['cost'] ?? 0)) ?> MWK</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Dates & Auto-Scheduling -->
                        <div class="card bg-light border p-3 rounded-3 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-event me-2 text-primary"></i>4. Dates & Incubation Schedule</h6>
                                <button type="button" class="btn btn-xs btn-outline-primary" id="btnRecalculateDates">
                                    <i class="bi bi-arrow-repeat me-1"></i>Auto-Recalculate Schedule
                                </button>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Date Received</label>
                                    <input type="date" name="date_received" id="dateReceived" class="form-control" value="<?= e($job['date_received'] ?? '') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Date Incubated</label>
                                    <input type="date" name="date_incubated" id="dateIncubated" class="form-control" value="<?= e($job['date_incubated'] ?? '') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Transfer Date</label>
                                    <input type="date" name="date_transfer" id="dateTransfer" class="form-control" value="<?= e($job['date_transfer'] ?? '') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Expected Hatch Date</label>
                                    <input type="date" name="expected_hatch_date" id="expectedHatchDate" class="form-control" value="<?= e($job['expected_hatch_date'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- 5. Status & Candling / Hatch Outcome -->
                        <div class="card bg-light border p-3 rounded-3 mb-4">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-check2-circle me-2 text-primary"></i>5. Status & Hatch Results</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Batch Status <span class="text-danger">*</span></label>
                                    <select name="status" id="statusSelect" class="form-select" required>
                                        <option value="received" <?= ($job['status'] === 'received') ? 'selected' : '' ?>>Received</option>
                                        <option value="incubating" <?= ($job['status'] === 'incubating') ? 'selected' : '' ?>>Incubating (in setter)</option>
                                        <option value="hatching" <?= ($job['status'] === 'hatching') ? 'selected' : '' ?>>Hatching (transferred to hatcher)</option>
                                        <option value="completed" <?= ($job['status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
                                        <option value="collected" <?= ($job['status'] === 'collected') ? 'selected' : '' ?>>Collected (Chicks Picked Up by Customer)</option>
                                        <option value="cancelled" <?= ($job['status'] === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Date Hatched (if completed)</label>
                                    <input type="date" name="date_hatched" class="form-control" value="<?= e($job['date_hatched'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Infertile / Clear Eggs</label>
                                    <input type="number" name="infertile" class="form-control" min="0" value="<?= $job['infertile'] !== null ? (int)$job['infertile'] : '' ?>" placeholder="Candling day count">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Fertile Eggs</label>
                                    <input type="number" name="fertile" class="form-control" min="0" value="<?= $job['fertile'] !== null ? (int)$job['fertile'] : '' ?>" placeholder="Developing embryos">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Hatched Chicks</label>
                                    <input type="number" name="hatched" class="form-control" min="0" value="<?= $job['hatched'] !== null ? (int)$job['hatched'] : '' ?>" placeholder="Successful chicks">
                                </div>
                            </div>
                        </div>

                        <!-- 6. Remarks -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark">Remarks / Special Notes</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Tray numbers, customer instructions, corrections made"><?= e($job['remarks'] ?? '') ?></textarea>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <div>
                                <?php if (\App\Core\Auth::hasRole(['owner', 'manager'])): ?>
                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteBatchModal">
                                        <i class="bi bi-trash3 me-1"></i>Delete Batch
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="<?= url("/incubation-jobs/{$job['id']}") ?>" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary fw-bold px-4">
                                    <i class="bi bi-save me-1"></i>Save Batch Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const eggsInc = document.getElementById('eggsIncubated');
    const rate = document.getElementById('ratePerEgg');
    const costDisp = document.getElementById('costDisplay');
    const speciesSel = document.getElementById('speciesSelect');
    const dateInc = document.getElementById('dateIncubated');
    const dateTrans = document.getElementById('dateTransfer');
    const dateHatch = document.getElementById('expectedHatchDate');
    const btnRecalc = document.getElementById('btnRecalculateDates');
    const speciesHelp = document.getElementById('speciesHelp');

    function updateCost() {
        const count = parseInt(eggsInc.value, 10) || 0;
        const perEgg = parseInt(rate.value, 10) || 0;
        const total = count * perEgg;
        costDisp.textContent = new Intl.NumberFormat().format(total) + ' MWK';
    }

    function calculateDates(force) {
        const incDateVal = dateInc.value;
        if (!incDateVal) return;
        const d = new Date(incDateVal);

        const selOpt = speciesSel.options[speciesSel.selectedIndex];
        const days = parseInt(selOpt.getAttribute('data-days'), 10) || 21;
        const transferDays = Math.max(1, days - 3);

        const tDate = new Date(d);
        tDate.setDate(tDate.getDate() + transferDays);

        const hDate = new Date(d);
        hDate.setDate(hDate.getDate() + days);

        const tIso = tDate.toISOString().split('T')[0];
        const hIso = hDate.toISOString().split('T')[0];

        if (force || !dateTrans.value) {
            dateTrans.value = tIso;
        }
        if (force || !dateHatch.value) {
            dateHatch.value = hIso;
        }

        const speciesName = selOpt.text.split('(')[0].trim();
        if (speciesHelp) {
            speciesHelp.textContent = `${speciesName}: Transfer Day ${transferDays} · Hatch Day ${days} (${days} days total)`;
        }
    }

    eggsInc.addEventListener('input', updateCost);
    rate.addEventListener('input', updateCost);
    speciesSel.addEventListener('change', function() {
        calculateDates(true);
    });
    btnRecalc.addEventListener('click', function() {
        calculateDates(true);
    });

    updateCost();
    calculateDates(false);
});
</script>
