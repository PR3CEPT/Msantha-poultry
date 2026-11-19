<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h1 class="h5 fw-bold mb-0 text-dark">
                        <i class="bi bi-egg-fried text-warning me-2"></i>Record Daily Egg Collection
                    </h1>
                    <a href="<?= url('/eggs') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="<?= url('/eggs') ?>" id="eggForm">
                        <?= csrf_field() ?>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Harvest Date <span class="text-danger">*</span></label>
                                <input type="date" name="prod_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Breed / Flock Type <span class="text-danger">*</span></label>
                                <select name="breed_id" id="breedSelect" class="form-select" required>
                                    <?php foreach ($breeds as $b): ?>
                                        <option value="<?= $b['id'] ?>" data-tray-price="<?= $b['egg_tray_price'] ?? 0 ?>">
                                            <?= e($b['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Specific Flock Batch (Optional)</label>
                            <select name="flock_id" class="form-select">
                                <option value="">-- General Harvest (Quails / Unassigned) --</option>
                                <?php foreach ($flocks as $f): ?>
                                    <option value="<?= $f['flock_id'] ?>">
                                        <?= e($f['batch_code']) ?> (<?= e($f['breed_name']) ?> &bull; Age: <?= (int)$f['age_weeks'] ?>w / <?= (int)$f['age_days'] ?>d / <?= (int)$f['age_months'] ?>m)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Quantities & Calculator Row -->
                        <div class="card bg-light border p-3 rounded-3 mb-3">
                            <h6 class="fw-bold text-dark mb-2">Egg Quantities & Standard Tray Calculator</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-dark">Eggs Collected <span class="text-danger">*</span></label>
                                    <input type="number" name="collected" id="collectedEggs" class="form-control fs-5 fw-bold" min="1" value="150" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-danger">Broken Eggs</label>
                                    <input type="number" name="broken" id="brokenEggs" class="form-control" min="0" value="0">
                                </div>
                                <div class="col-12">
                                    <div class="p-3 bg-white rounded-3 border d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="text-muted small d-block">Equivalent Standard Trays (30 eggs/tray):</span>
                                            <strong class="fs-4 text-warning-emphasis" id="trayDisplay">5 Trays</strong>
                                            <span class="text-muted small ms-1" id="remainderDisplay">(0 loose)</span>
                                        </div>
                                        <div class="text-end">
                                            <span class="text-muted small d-block">Estimated Tray Value:</span>
                                            <strong class="fs-5 text-success" id="valueDisplay">—</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Distribution Row -->
                        <div class="card bg-light border p-3 rounded-3 mb-4">
                            <h6 class="fw-bold text-dark mb-2">Harvest Distribution</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-muted">To Incubator</label>
                                    <input type="number" name="incubated" class="form-control" min="0" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-muted">Sold</label>
                                    <input type="number" name="sold" class="form-control" min="0" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-muted">Staff Consumed</label>
                                    <input type="number" name="consumed" class="form-control" min="0" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark">Remarks / Pen Observation</label>
                            <input type="text" name="remarks" class="form-control" placeholder="e.g. Morning collection, egg size uniform">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning fw-bold flex-grow-1 text-dark rounded-pill shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i>Save Egg Harvest
                            </button>
                            <a href="<?= url('/eggs') ?>" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const collInput = document.getElementById('collectedEggs');
    const trayDisp = document.getElementById('trayDisplay');
    const remDisp = document.getElementById('remainderDisplay');
    const valDisp = document.getElementById('valueDisplay');
    const breedSel = document.getElementById('breedSelect');

    function calculate() {
        const count = parseInt(collInput.value, 10) || 0;
        const trays = Math.floor(count / 30);
        const rem = count % 30;

        trayDisp.textContent = trays + ' Trays';
        remDisp.textContent = '(' + rem + ' loose)';

        const sel = breedSel.options[breedSel.selectedIndex];
        const trayPrice = parseInt(sel.getAttribute('data-tray-price'), 10) || 0;

        if (trayPrice > 0 && trays > 0) {
            valDisp.textContent = new Intl.NumberFormat().format(trays * trayPrice) + ' MWK';
        } else {
            valDisp.textContent = '—';
        }
    }

    collInput.addEventListener('input', calculate);
    breedSel.addEventListener('change', calculate);
    calculate();
});
</script>

