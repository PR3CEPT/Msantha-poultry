<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="h5 fw-bold mb-0 text-dark">
                            <i class="bi bi-thermometer-half text-primary me-2"></i>Receive Eggs for Incubation
                        </h1>
                        <a href="<?= url('/incubation-jobs') ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($flashError)): ?>
                        <div class="alert alert-danger mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($flashError) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= url('/incubation-jobs') ?>" id="receiveEggsForm">
                        <?= csrf_field() ?>

                        <!-- Customer Selection Section -->
                        <div class="card bg-light border p-3 rounded-3 mb-4">
                            <h6 class="fw-bold text-dark mb-3">1. Customer Information</h6>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Select Existing Customer</label>
                                <select name="customer_id" id="customerSelect" class="form-select">
                                    <option value="0">-- Or enter a new customer below --</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= ($preselectedCustomerId === (int)$c['id']) ? 'selected' : '' ?>>
                                            <?= e($c['name']) ?> (<?= e($c['phone'] ?? 'No phone') ?>, <?= e($c['location'] ?? 'No loc') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div id="newCustomerFields" class="row g-2">
                                <div class="col-md-7">
                                    <label class="form-label small text-muted">New Customer Name</label>
                                    <input type="text" name="new_customer_name" class="form-control" placeholder="If not in list, enter full name">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small text-muted">Primary Phone</label>
                                    <input type="text" name="new_customer_phone" class="form-control" placeholder="0999... or +265...">
                                </div>
                            </div>
                        </div>

                        <!-- Species & Breed Section -->
                        <div class="card bg-light border p-3 rounded-3 mb-4">
                            <h6 class="fw-bold text-dark mb-3">2. Species & Breed Setup</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Species <span class="text-danger">*</span></label>
                                    <select name="species_id" id="speciesSelect" class="form-select" required>
                                        <?php foreach ($species as $s): ?>
                                            <option value="<?= $s['id'] ?>" data-days="<?= $s['incubation_days'] ?>">
                                                <?= e($s['name']) ?> (<?= $s['incubation_days'] ?> Days total)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small id="speciesHelp" class="text-muted d-block mt-1">
                                        Chicken: Transfer Day 18 &middot; Hatch Day 21
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Breed (Optional)</label>
                                    <select name="breed_id" class="form-select">
                                        <option value="">-- Unspecified / Mixed --</option>
                                        <?php foreach ($breeds as $b): ?>
                                            <option value="<?= $b['id'] ?>"><?= e($b['name']) ?> (<?= e($b['species_name']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Quantities & Financials Section -->
                        <div class="card bg-light border p-3 rounded-3 mb-4">
                            <h6 class="fw-bold text-dark mb-3">3. Quantities & Hatchery Fees</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Eggs Received <span class="text-danger">*</span></label>
                                    <input type="number" name="eggs_received" id="eggsReceived" class="form-control" required min="1" value="100">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Eggs Incubated <span class="text-danger">*</span></label>
                                    <input type="number" name="eggs_incubated" id="eggsIncubated" class="form-control" required min="1" value="100">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Rate Per Egg (MWK)</label>
                                    <input type="number" name="rate_per_egg" id="ratePerEgg" class="form-control" required min="0" value="120">
                                </div>
                                <div class="col-12">
                                    <div class="p-3 bg-white rounded-3 border d-flex justify-content-between align-items-center">
                                        <span class="fw-semibold text-muted">Auto-Calculated Total Cost:</span>
                                        <span class="fs-4 fw-bold text-primary" id="costDisplay">12,000 MWK</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dates Section -->
                        <div class="card bg-light border p-3 rounded-3 mb-4">
                            <h6 class="fw-bold text-dark mb-3">4. Dates & Auto-Scheduling</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Date Received</label>
                                    <input type="date" name="date_received" id="dateReceived" class="form-control" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Date Setting in Incubator</label>
                                    <input type="date" name="date_incubated" id="dateIncubated" class="form-control" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-info py-2 mb-0 small">
                                        <i class="bi bi-calendar-check me-1"></i>
                                        Expected Transfer Date: <strong id="transferPreview">—</strong> &middot;
                                        Expected Hatch Date: <strong id="hatchPreview">—</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark">Remarks / Special Instructions</label>
                            <input type="text" name="remarks" class="form-control" placeholder="e.g. Tray numbers, customer instructions">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success fw-bold flex-grow-1">
                                <i class="bi bi-check2-circle me-1"></i>Create Incubation Batch
                            </button>
                            <a href="<?= url('/incubation-jobs') ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const eggsInc = document.getElementById('eggsIncubated');
    const eggsRec = document.getElementById('eggsReceived');
    const rate = document.getElementById('ratePerEgg');
    const costDisp = document.getElementById('costDisplay');
    const speciesSel = document.getElementById('speciesSelect');
    const dateInc = document.getElementById('dateIncubated');
    const transferPrev = document.getElementById('transferPreview');
    const hatchPrev = document.getElementById('hatchPreview');

    function updateCost() {
        const count = parseInt(eggsInc.value, 10) || 0;
        const perEgg = parseInt(rate.value, 10) || 0;
        const total = count * perEgg;
        costDisp.textContent = new Intl.NumberFormat().format(total) + ' MWK';
    }

    function updateDates() {
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

        transferPrev.textContent = tDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        hatchPrev.textContent = hDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    eggsRec.addEventListener('input', function() {
        if (!eggsInc.value || eggsInc.value === eggsRec.defaultValue) {
            eggsInc.value = eggsRec.value;
        }
        updateCost();
    });

    eggsInc.addEventListener('input', updateCost);
    rate.addEventListener('input', updateCost);
    speciesSel.addEventListener('change', updateDates);
    dateInc.addEventListener('change', updateDates);

    updateCost();
    updateDates();
});
</script>

