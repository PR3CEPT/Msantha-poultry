<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h1 class="h5 fw-bold mb-0 text-dark">
                        <i class="bi bi-feather text-success me-2"></i>New Flock Intake Batch
                    </h1>
                    <a href="<?= url('/flocks') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="<?= url('/flocks') ?>">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Batch Code / Identifier <span class="text-danger">*</span></label>
                            <input type="text" name="batch_code" class="form-control" required placeholder="e.g. SASSO-3(1), MIKOLONGWE-9">
                            <small class="text-muted">Unique batch code assigned to this pen or house.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Breed & Species <span class="text-danger">*</span></label>
                            <select name="breed_id" class="form-select" required>
                                <?php foreach ($breeds as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Initial Birds Count <span class="text-danger">*</span></label>
                                <input type="number" name="start_count" class="form-control" min="1" value="500" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Flock Purpose</label>
                                <select name="purpose" class="form-select">
                                    <option value="layers">Layers (Egg Production)</option>
                                    <option value="broilers">Broilers (Meat)</option>
                                    <option value="breeders">Breeders (Parent Stock)</option>
                                    <option value="mixed" selected>Dual Purpose / Mixed</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Source Origin</label>
                                <select name="source" class="form-select">
                                    <option value="hatched_own" selected>Hatched on Farm</option>
                                    <option value="purchased">Purchased Day-Old Chicks</option>
                                    <option value="incubation">Custom Incubation</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark">Pen Notes / Batch Remarks</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="House number, feed notes, or supplier..."></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success fw-bold flex-grow-1">
                                <i class="bi bi-check2-circle me-1"></i>Register Flock Batch
                            </button>
                            <a href="<?= url('/flocks') ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

