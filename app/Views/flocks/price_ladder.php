<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('/flocks') ?>" class="btn btn-sm btn-light border text-muted">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0 text-dark">Age-Based Price Ladder</h1>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">Selling Matrix</span>
            </div>
            <p class="text-muted small mb-0 mt-1">Configures live bird selling price tiers by bird age in weeks (derived from "NEW PRICE EFFECTED" legacy schedules).</p>
        </div>
        <div>
            <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#newTierModal">
                <i class="bi bi-plus-circle me-1"></i>Add Price Tier
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

    <!-- Breed Selector Tabs -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <?php foreach ($breeds as $b): ?>
            <a href="<?= url("/flocks/price-ladder?breed_id={$b['id']}") ?>" 
               class="btn btn-sm <?= ($selectedBreedId === (int)$b['id']) ? 'btn-success fw-bold' : 'btn-outline-secondary' ?>">
                <?= e($b['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Price Ladder Table -->
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark fs-6">Current Ladder Tiers for Selected Breed</h5>
            <small class="text-muted">Evaluated dynamically by <code>PricingService</code></small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3">Starting Age</th>
                        <th class="text-end">Selling Price (MWK)</th>
                        <th>Effective From Date</th>
                        <th class="text-end pe-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ladder)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                No specific price tiers configured for this breed yet. Standard base price applies.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ladder as $tier): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark fs-6">
                                    Week <?= $tier['from_week'] ?>+
                                    <small class="text-muted fw-normal d-block">
                                        &ge; <?= $tier['from_week'] ?> weeks old (&ge; <?= $tier['from_week'] * 7 ?> days &bull; ~<?= round(($tier['from_week'] * 7) / 30.4, 1) ?> months)
                                    </small>
                                </td>
                                <td class="text-end fw-bold text-success fs-5">
                                    <?= number_format((int)$tier['price']) ?> <small class="fs-6 text-muted">MWK</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= date('d-M-Y', strtotime($tier['effective_from'])) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active Tier</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Tier Modal -->
<div class="modal fade" id="newTierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('/flocks/price-ladder') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="breed_id" value="<?= $selectedBreedId ?>">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Price Ladder Tier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">From Bird Age (Weeks) <span class="text-danger">*</span></label>
                        <input type="number" name="from_week" class="form-control" min="1" max="150" value="8" required>
                        <small class="text-muted">Applies to birds aged this week or older.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Selling Price (MWK) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control fs-5 fw-bold" min="100" step="100" value="8500" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Effective From Date</label>
                        <input type="date" name="effective_from" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">Save Price Tier</button>
                </div>
            </form>
        </div>
    </div>
</div>

