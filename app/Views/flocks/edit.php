<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h5 fw-bold mb-0 text-dark">
                            <i class="bi bi-pencil-square text-success me-2"></i>Edit Flock Batch
                        </h1>
                        <small class="text-muted">Batch Reference #<?= e((string)$flock['id']) ?></small>
                    </div>
                    <a href="<?= url("/flocks/{$flock['id']}") ?>" class="btn btn-sm btn-outline-secondary rounded-pill">
                        <i class="bi bi-arrow-left me-1"></i>Back to Batch
                    </a>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($flashError)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($flashError) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= url("/flocks/{$flock['id']}") ?>">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Batch Code / Identifier <span class="text-danger">*</span></label>
                            <input type="text" name="batch_code" class="form-control" required value="<?= e($flock['batch_code']) ?>">
                            <small class="text-muted">Unique batch code assigned to this pen or house.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Breed & Species <span class="text-danger">*</span></label>
                            <select name="breed_id" class="form-select" required>
                                <?php foreach ($breeds as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= (int)$flock['breed_id'] === (int)$b['id'] ? 'selected' : '' ?>>
                                        <?= e($b['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" value="<?= e($flock['start_date']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Initial Birds Count <span class="text-danger">*</span></label>
                                <input type="number" name="start_count" class="form-control" min="1" value="<?= e((string)$flock['start_count']) ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Flock Purpose</label>
                                <select name="purpose" class="form-select">
                                    <option value="layers" <?= $flock['purpose'] === 'layers' ? 'selected' : '' ?>>Layers (Egg Production)</option>
                                    <option value="broilers" <?= $flock['purpose'] === 'broilers' ? 'selected' : '' ?>>Broilers (Meat)</option>
                                    <option value="breeders" <?= $flock['purpose'] === 'breeders' ? 'selected' : '' ?>>Breeders (Parent Stock)</option>
                                    <option value="mixed" <?= $flock['purpose'] === 'mixed' ? 'selected' : '' ?>>Dual Purpose / Mixed</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Source Origin</label>
                                <select name="source" class="form-select">
                                    <option value="hatched_own" <?= $flock['source'] === 'hatched_own' ? 'selected' : '' ?>>Hatched on Farm</option>
                                    <option value="purchased" <?= $flock['source'] === 'purchased' ? 'selected' : '' ?>>Purchased Day-Old Chicks</option>
                                    <option value="incubation" <?= $flock['source'] === 'incubation' ? 'selected' : '' ?>>Custom Incubation</option>
                                    <option value="other" <?= $flock['source'] === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Batch Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $flock['status'] === 'active' ? 'selected' : '' ?>>Active (Currently in house)</option>
                                <option value="closed" <?= $flock['status'] === 'closed' ? 'selected' : '' ?>>Closed (Depleted / Sold Out)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark">Pen Notes / Batch Remarks</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="House number, feed notes, or supplier..."><?= e($flock['notes'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success fw-bold flex-grow-1 rounded-pill">
                                <i class="bi bi-check2-circle me-1"></i>Save Changes
                            </button>
                            <a href="<?= url("/flocks/{$flock['id']}") ?>" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
