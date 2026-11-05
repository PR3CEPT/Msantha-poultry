<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h1 class="h5 fw-bold mb-0 text-dark">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Quick Daily Flock Entry
                    </h1>
                    <a href="<?= url('/flocks') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($flashSuccess)): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i><?= e($flashSuccess) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($flashError)): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($flashError) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= url('/flocks/quick-entry') ?>">
                        <?= csrf_field() ?>

                        <!-- Target Flock and Date -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold text-dark">Select Target Flock <span class="text-danger">*</span></label>
                                <select name="flock_id" class="form-select fs-6" required>
                                    <option value="">-- Choose active flock batch --</option>
                                    <?php foreach ($activeFlocks as $f): ?>
                                        <option value="<?= $f['flock_id'] ?>" <?= ($selectedFlockId === (int)$f['flock_id']) ? 'selected' : '' ?>>
                                            <?= e($f['batch_code']) ?> (<?= e($f['breed_name']) ?> &middot; <?= number_format((int)$f['current_stock']) ?> birds)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold text-dark">Log Date <span class="text-danger">*</span></label>
                                <input type="date" name="entry_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <!-- Dynamics Row: Deaths, Sales, Culls -->
                        <div class="card bg-light border p-3 rounded-3 mb-4">
                            <h6 class="fw-bold text-dark mb-3">Daily Bird Movements (Offloads & Losses)</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-danger">
                                        <i class="bi bi-heart-pulse me-1"></i>Deaths Today
                                    </label>
                                    <input type="number" name="deaths" class="form-control border-danger" min="0" value="0">
                                    <small class="text-muted">Triggers alert if &ge; 2%</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-success">
                                        <i class="bi bi-cart-check me-1"></i>Birds Sold
                                    </label>
                                    <input type="number" name="sold" class="form-control" min="0" value="0">
                                    <small class="text-muted">Quantity offloaded</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-dark">Unit Price (MWK)</label>
                                    <input type="number" name="sold_price" class="form-control" min="0" placeholder="e.g. 8500">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Customer (Optional)</label>
                                    <select name="customer_id" class="form-select form-select-sm">
                                        <option value="">-- Cash / Walk-in Customer --</option>
                                        <?php foreach ($customers as $c): ?>
                                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['phone'] ?? '') ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Culled Birds</label>
                                    <input type="number" name="culled" class="form-control form-control-sm" min="0" value="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Consumed (Staff)</label>
                                    <input type="number" name="consumed" class="form-control form-control-sm" min="0" value="0">
                                </div>
                            </div>
                        </div>

                        <!-- Additions & Health Row -->
                        <div class="card bg-light border p-3 rounded-3 mb-4">
                            <h6 class="fw-bold text-dark mb-3">Additions & Health Interventions</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-primary">
                                        <i class="bi bi-plus-circle me-1"></i>Birds Added
                                    </label>
                                    <input type="number" name="added" class="form-control" min="0" value="0">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold text-dark">
                                        <i class="bi bi-shield-plus text-info me-1"></i>Vaccine Administered (Optional)
                                    </label>
                                    <input type="text" name="vaccine" class="form-control" placeholder="e.g. Gumboro IBD, Newcastle Lasota">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark">General Remarks / Notes</label>
                            <input type="text" name="remarks" class="form-control" placeholder="e.g. House humidity normal, water intake good...">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary fw-bold flex-grow-1">
                                <i class="bi bi-check2-circle me-1"></i>Save Daily Record
                            </button>
                            <a href="<?= url('/flocks') ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

