<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 fw-bold mb-0 text-dark">Feed & Drugs Inventory & Costing</h1>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">Stock Inputs</span>
            </div>
            <p class="text-muted small mb-0 mt-1">Track feed batches, medication expenses, and input costs per bird and per harvested egg.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#newFeedModal">
                <i class="bi bi-plus-circle me-1"></i>Add Feed Purchase
            </button>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newDrugModal">
                <i class="bi bi-capsule me-1"></i>Add Drug Purchase
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

    <!-- KPI Summary Row & Feed Cost Per Bird / Egg Report -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                <span class="text-muted small fw-semibold text-uppercase">Total Feed Spend</span>
                <div class="fs-3 fw-bold text-success mt-1 font-monospace"><?= number_format($stats['total_feed_cost']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted font-monospace"><?= number_format($stats['total_feed_kg'], 1) ?> kg purchased</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary">
                <span class="text-muted small fw-semibold text-uppercase">Total Drugs Spend</span>
                <div class="fs-3 fw-bold text-primary mt-1 font-monospace"><?= number_format($stats['total_drug_cost']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted">Vaccines & treatments</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-info">
                <span class="text-muted small fw-semibold text-uppercase">Feed Cost / Bird</span>
                <div class="fs-3 fw-bold text-dark mt-1 font-monospace"><?= number_format($stats['cost_per_bird']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted">Over <?= number_format($totalBirdsStocked) ?> stocked birds</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-warning">
                <span class="text-muted small fw-semibold text-uppercase">Feed Cost / Egg</span>
                <div class="fs-3 fw-bold text-warning-emphasis mt-1 font-monospace"><?= number_format($stats['cost_per_egg']) ?> <small class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted">Over <?= number_format($totalEggs) ?> collected eggs</small>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 gap-2">
        <li class="nav-item">
            <a class="nav-link <?= ($tab ?? 'feed') === 'feed' ? 'active bg-success text-white fw-bold' : 'bg-white border text-dark' ?>" 
               href="<?= url('/feed-drugs?tab=feed') ?>">
                <i class="bi bi-box-seam me-1"></i>Feed Purchases
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($tab ?? '') === 'drugs' ? 'active bg-primary text-white fw-bold' : 'bg-white border text-dark' ?>" 
               href="<?= url('/feed-drugs?tab=drugs') ?>">
                <i class="bi bi-capsule me-1"></i>Drug & Vaccine Purchases
            </a>
        </li>
    </ul>

    <?php if (($tab ?? 'feed') === 'drugs'): ?>
        <!-- Drugs Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-capsule me-2 text-primary"></i>Medication & Vaccine Acquisition Log</h5>
                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                    Showing <?= count($drugPurchases) ?> of <?= number_format($totalRows ?? count($drugPurchases)) ?> Purchases
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 font-monospace">
                    <thead class="table-light text-muted small text-uppercase" style="font-size: 0.72rem;">
                        <tr>
                            <th class="ps-3 py-3" style="width: 140px; font-family: inherit;">Purchase Date</th>
                            <th class="py-3" style="font-family: inherit;">Drug / Vaccine Item</th>
                            <th class="text-end py-3" style="width: 130px;">Doses</th>
                            <th class="text-end pe-3 py-3" style="width: 160px;">Cost (MWK)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($drugPurchases)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted font-sans-serif">No drug purchases recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($drugPurchases as $dp): ?>
                                <tr>
                                    <td class="ps-3 fw-semibold font-monospace"><?= date('d-M-Y', strtotime($dp['purchase_date'])) ?></td>
                                    <td class="fw-bold text-dark font-sans-serif"><?= e($dp['item']) ?></td>
                                    <td class="text-end text-muted"><?= $dp['doses'] ? number_format((int)$dp['doses']) : '—' ?></td>
                                    <td class="text-end pe-3 fw-bold text-primary"><?= number_format((int)$dp['cost']) ?> MWK</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if (!empty($totalPages) && $totalPages > 1): ?>
                <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center p-3">
                    <span class="text-muted small">Showing page <?= $page ?> of <?= $totalPages ?> (<?= number_format($totalRows) ?> total purchases)</span>
                    <nav aria-label="Drugs navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= url('/feed-drugs?tab=drugs&page=' . ($page - 1)) ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            <li class="page-item active">
                                <span class="page-link"><?= $page ?></span>
                            </li>
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= url('/feed-drugs?tab=drugs&page=' . ($page + 1)) ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- Feed Purchases Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-box-seam me-2 text-success"></i>Feed Inventory & Procurement Log</h5>
                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                    Showing <?= count($feedPurchases) ?> of <?= number_format($totalRows ?? count($feedPurchases)) ?> Purchases
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 font-monospace">
                    <thead class="table-light text-muted small text-uppercase" style="font-size: 0.72rem;">
                        <tr>
                            <th class="ps-3 py-3" style="width: 140px; font-family: inherit;">Purchase Date</th>
                            <th class="py-3" style="font-family: inherit;">Feed Type</th>
                            <th class="text-end py-3" style="width: 140px;">Quantity (kg)</th>
                            <th class="text-end py-3" style="width: 130px;">Unit Cost / kg</th>
                            <th class="text-end py-3" style="width: 150px;">Total Cost (MWK)</th>
                            <th class="pe-3 py-3" style="font-family: inherit;">Supplier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($feedPurchases)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted font-sans-serif">No feed purchases recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($feedPurchases as $fp): ?>
                                <?php
                                $kg = (float) $fp['quantity_kg'];
                                $c = (int) $fp['cost'];
                                $perKg = $kg > 0 ? round($c / $kg) : 0;
                                ?>
                                <tr>
                                    <td class="ps-3 fw-semibold font-monospace"><?= date('d-M-Y', strtotime($fp['purchase_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= e($fp['feed_type_name']) ?></span>
                                    </td>
                                    <td class="text-end fw-bold text-dark"><?= number_format($kg, 1) ?> kg</td>
                                    <td class="text-end text-muted"><?= number_format($perKg) ?> MWK</td>
                                    <td class="text-end fw-bold text-success"><?= number_format($c) ?> MWK</td>
                                    <td class="pe-3 text-muted small font-sans-serif"><?= e($fp['supplier'] ?? 'Local Feed Supplier') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if (!empty($totalPages) && $totalPages > 1): ?>
                <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center p-3">
                    <span class="text-muted small">Showing page <?= $page ?> of <?= $totalPages ?> (<?= number_format($totalRows) ?> total purchases)</span>
                    <nav aria-label="Feed navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= url('/feed-drugs?tab=feed&page=' . ($page - 1)) ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            <li class="page-item active">
                                <span class="page-link"><?= $page ?></span>
                            </li>
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= url('/feed-drugs?tab=feed&page=' . ($page + 1)) ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add Feed Purchase Modal -->
<div class="modal fade" id="newFeedModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('/feed-drugs/feed') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Record Feed Purchase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Feed Type</label>
                        <select name="feed_type_id" class="form-select" required>
                            <?php foreach ($feedTypes as $ft): ?>
                                <option value="<?= $ft['id'] ?>"><?= e($ft['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Quantity (kg)</label>
                            <input type="number" step="0.5" name="quantity_kg" class="form-control" value="50" min="0.5" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Total Cost (MWK)</label>
                            <input type="number" name="cost" class="form-control fs-5 fw-bold" min="100" value="45000" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Supplier / Shop</label>
                        <input type="text" name="supplier" class="form-control" placeholder="e.g. Proto Feeds, Farmers World">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">Save Feed Purchase</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Drug Purchase Modal -->
<div class="modal fade" id="newDrugModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('/feed-drugs/drug') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Record Drug / Vaccine Purchase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Drug / Vaccine Item Name</label>
                        <input type="text" name="item" class="form-control" placeholder="e.g. Gumboro IBD, Oxytetracycline 20%" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Doses / Quantity</label>
                            <input type="number" name="doses" class="form-control" min="0" placeholder="e.g. 500">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Total Cost (MWK)</label>
                            <input type="number" name="cost" class="form-control fs-5 fw-bold" min="100" value="15000" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Save Drug Purchase</button>
                </div>
            </form>
        </div>
    </div>
</div>

