<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 fw-bold mb-0 text-dark">Flock Management</h1>
                <span class="badge bg-success-subtle text-success border border-success-subtle fs-6">
                    <?= number_format($stats['active_flocks']) ?> Active Flocks
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Track bird counts, daily dynamics, authoritative mortality events, and egg prices across 28 batches.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= url('/flocks/quick-entry') ?>" class="btn btn-primary fw-bold">
                <i class="bi bi-pencil-square me-1"></i>Quick Daily Entry
            </a>
            <a href="<?= url('/flocks/create') ?>" class="btn btn-success fw-bold">
                <i class="bi bi-plus-circle me-1"></i>New Batch
            </a>
            <a href="<?= url('/flocks?tab=batches') ?>" class="btn <?= ($tab ?? 'batches') === 'batches' ? 'btn-outline-dark active' : 'btn-outline-secondary' ?>">
                <i class="bi bi-grid-3x3-gap me-1"></i>Batches
            </a>
            <a href="<?= url('/flocks?tab=events') ?>" class="btn <?= ($tab ?? '') === 'events' ? 'btn-danger' : 'btn-outline-secondary' ?>">
                <i class="bi bi-heart-pulse me-1"></i>Mortality
            </a>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-success">
                <span class="text-muted small fw-semibold text-uppercase">Live Stock</span>
                <div class="fs-3 fw-bold text-success mt-1"><?= number_format($stats['current_stock']) ?></div>
                <small class="text-muted">Birds in pens</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-primary">
                <span class="text-muted small fw-semibold text-uppercase">Initial Stocked</span>
                <div class="fs-3 fw-bold text-primary mt-1"><?= number_format($stats['total_initial']) ?></div>
                <small class="text-muted">Total chick intakes</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-danger">
                <span class="text-muted small fw-semibold text-uppercase">Total Deaths</span>
                <div class="fs-3 fw-bold text-danger mt-1"><?= number_format($stats['total_deaths']) ?></div>
                <small class="text-muted">Recorded mortality</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-info">
                <span class="text-muted small fw-semibold text-uppercase">Total Sold</span>
                <div class="fs-3 fw-bold text-info mt-1"><?= number_format($stats['total_sold']) ?></div>
                <small class="text-muted">Live birds offloaded</small>
            </div>
        </div>
    </div>

    <?php if (($tab ?? 'batches') === 'events'): ?>
        <!-- EVENTS TAB -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom p-3">
                <form method="GET" action="<?= url('/flocks') ?>" class="row g-2 align-items-center">
                    <input type="hidden" name="tab" value="events">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" value="<?= e($search ?? '') ?>" class="form-control" placeholder="Search batch, breed or remarks...">
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-filter me-1"></i>Filter</button>
                        <?php if (!empty($search)): ?>
                            <a href="<?= url('/flocks?tab=events') ?>" class="btn btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3 py-3" style="width: 140px;">Event Date</th>
                            <th class="py-3" style="width: 150px;">Flock Batch</th>
                            <th class="py-3">Breed</th>
                            <th class="py-3" style="width: 130px;">Event Type</th>
                            <th class="text-end py-3" style="width: 130px;">Quantity</th>
                            <th class="pe-3 py-3">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($events)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    No flock events found matching criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($events as $e): ?>
                                <tr>
                                    <td class="ps-3 fw-semibold text-dark">
                                        <?= date('d-M-Y', strtotime($e['event_date'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= e($e['batch_code']) ?></span>
                                    </td>
                                    <td><?= e($e['breed_name']) ?></td>
                                    <td>
                                        <?php if ($e['event_type'] === 'death'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                <i class="bi bi-x-circle me-1"></i>DEATH
                                            </span>
                                        <?php elseif ($e['event_type'] === 'sale'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="bi bi-cart-check me-1"></i>SOLD
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= strtoupper(e($e['event_type'])) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold fs-6 <?= $e['event_type'] === 'death' ? 'text-danger' : 'text-success' ?>">
                                        <?= number_format((int)$e['quantity']) ?>
                                    </td>
                                    <td class="pe-3 small text-muted"><?= e($e['remarks'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
        <!-- BATCHES TAB -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom p-3">
                <form method="GET" action="<?= url('/flocks') ?>" class="row g-2 align-items-center">
                    <input type="hidden" name="tab" value="batches">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" value="<?= e($search ?? '') ?>" class="form-control" placeholder="Search batch code or breed...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select name="status" class="form-select">
                            <option value="all" <?= ($statusFilter ?? '') === 'all' ? 'selected' : '' ?>>All Statuses</option>
                            <option value="active" <?= ($statusFilter ?? '') === 'active' ? 'selected' : '' ?>>Active (Stock > 0)</option>
                            <option value="closed" <?= ($statusFilter ?? '') === 'closed' ? 'selected' : '' ?>>Closed / Depleted (Stock = 0)</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-filter me-1"></i>Filter</button>
                        <?php if (!empty($search) || ($statusFilter ?? 'all') !== 'all'): ?>
                            <a href="<?= url('/flocks') ?>" class="btn btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Responsive Table for Desktop -->
            <div class="table-responsive d-none d-md-block">
                <table class="table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3 py-3" style="width: 150px;">Batch Code</th>
                            <th class="py-3">Breed & Species</th>
                            <th class="py-3" style="width: 140px;">Intake Date</th>
                            <th class="text-end py-3" style="width: 120px;">Initial Stock</th>
                            <th class="text-end py-3" style="width: 130px;">Current Stock</th>
                            <th class="text-end py-3" style="width: 130px;">Mortality</th>
                            <th class="text-end py-3" style="width: 140px;">Egg Tray Price</th>
                            <th class="text-center pe-3 py-3" style="width: 110px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($flocks)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    No flocks found matching criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($flocks as $f): ?>
                                <?php
                                $stock = (int) $f['current_stock'];
                                $initial = (int) $f['starting_count'];
                                $deaths = (int) $f['deaths_todate'];
                                $mortalityRate = $initial > 0 ? round(($deaths / $initial) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td class="ps-3">
                                        <a href="<?= url("/flocks/{$f['flock_id']}") ?>" class="fw-bold text-dark text-decoration-none">
                                            <?= e($f['batch_code']) ?> <i class="bi bi-box-arrow-up-right small text-muted ms-1" style="font-size: 0.72rem;"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= e($f['breed_name']) ?></span>
                                    </td>
                                    <td class="text-muted small">
                                        <?= !empty($f['start_date']) ? date('d-M-Y', strtotime($f['start_date'])) : '—' ?>
                                    </td>
                                    <td class="text-end text-muted fw-semibold">
                                        <?= number_format($initial) ?>
                                    </td>
                                    <td class="text-end">
                                        <span class="fs-6 fw-bold <?= $stock > 0 ? 'text-success' : 'text-secondary' ?>">
                                            <?= number_format($stock) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-danger fw-semibold"><?= number_format($deaths) ?></span>
                                        <small class="text-muted">(<?= $mortalityRate ?>%)</small>
                                    </td>
                                    <td class="text-end fw-semibold text-muted">
                                        <?= !empty($f['egg_tray_price']) ? number_format((int)$f['egg_tray_price']) . ' MWK' : '—' ?>
                                    </td>
                                    <td class="text-center pe-3">
                                        <?php if ($stock > 0): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                <i class="bi bi-check-circle me-1"></i>Active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border px-2 py-1">Depleted</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View (< 768px) -->
            <div class="d-md-none p-3">
                <?php if (empty($flocks)): ?>
                    <div class="text-center py-4 text-muted">No flocks found.</div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($flocks as $f): ?>
                            <?php
                            $stock = (int) $f['current_stock'];
                            $initial = (int) $f['starting_count'];
                            $deaths = (int) $f['deaths_todate'];
                            ?>
                            <div class="card border rounded-3 p-3 shadow-sm bg-white">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <a href="<?= url("/flocks/{$f['flock_id']}") ?>" class="fw-bold text-dark text-decoration-none fs-6">
                                            <?= e($f['batch_code']) ?> <i class="bi bi-chevron-right small text-muted"></i>
                                        </a>
                                        <small class="text-muted d-block"><?= e($f['breed_name']) ?></small>
                                    </div>
                                    <?php if ($stock > 0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border">Depleted</span>
                                    <?php endif; ?>
                                </div>
                                <div class="row g-2 text-center my-1 py-2 bg-light rounded-2">
                                    <div class="col-4">
                                        <small class="text-muted d-block">Current</small>
                                        <span class="fw-bold fs-6 <?= $stock > 0 ? 'text-success' : 'text-secondary' ?>"><?= number_format($stock) ?></span>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Initial</small>
                                        <span class="fw-semibold text-dark"><?= number_format($initial) ?></span>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Deaths</small>
                                        <span class="fw-semibold text-danger"><?= number_format($deaths) ?></span>
                                    </div>
                                </div>
                                <?php if (!empty($f['egg_tray_price'])): ?>
                                    <div class="small text-muted mt-2 d-flex justify-content-between">
                                        <span>Egg Tray Price:</span>
                                        <strong class="text-dark"><?= number_format((int)$f['egg_tray_price']) ?> MWK</strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

