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
            <?php if (\App\Core\Auth::can('flocks', 'create')): ?>
            <a href="<?= url('/flocks/quick-entry') ?>" class="btn btn-primary fw-bold">
                <i class="bi bi-pencil-square me-1"></i>Quick Daily Entry
            </a>
            <a href="<?= url('/flocks/create') ?>" class="btn btn-success fw-bold">
                <i class="bi bi-plus-circle me-1"></i>New Batch
            </a>
            <?php endif; ?>
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
        <!-- EVENTS TAB (Clean Table with Pagination) -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
            <form method="GET" action="<?= url('/flocks') ?>" class="row g-2 align-items-center">
                <input type="hidden" name="tab" value="events">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="<?= e($search ?? '') ?>" class="form-control form-control-sm border-start-0" placeholder="Search batch, breed or remarks...">
                    </div>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <?php if (!empty($search)): ?>
                        <a href="<?= url('/flocks?tab=events') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-2.5">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark fs-6"><i class="bi bi-heart-pulse me-1 text-danger"></i>Mortality & Dynamics Event Log</span>
                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                    Showing <?= count($events) ?> of <?= number_format($totalRows ?? count($events)) ?> Events
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase font-monospace" style="font-size: 0.72rem;">
                        <tr>
                            <th class="ps-3 py-3" style="width: 120px;">Date</th>
                            <th class="py-3">Batch Code</th>
                            <th class="py-3">Breed</th>
                            <th class="py-3" style="width: 130px;">Event Type</th>
                            <th class="text-end py-3" style="width: 130px;">Quantity</th>
                            <th class="py-3">Remarks / Cause</th>
                            <th class="text-end pe-3 py-3" style="width: 110px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($events)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-heart-pulse fs-2 d-block mb-2 text-secondary"></i>
                                    No flock events recorded matching the filter criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($events as $e): ?>
                                <?php
                                $isDeath = ($e['event_type'] === 'death');
                                $isSale = ($e['event_type'] === 'sale');
                                $qty = (int) $e['quantity'];
                                ?>
                                <tr>
                                    <td class="ps-3 font-monospace small text-dark fw-semibold">
                                        <?= date('d-M-Y', strtotime($e['event_date'])) ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark font-monospace"><?= e($e['batch_code']) ?></span>
                                    </td>
                                    <td class="small text-muted">
                                        <?= e($e['breed_name']) ?>
                                    </td>
                                    <td>
                                        <?php if ($isDeath): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Mortality (Death)</span>
                                        <?php elseif ($isSale): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Offloaded / Sold</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border"><?= strtoupper(e($e['event_type'])) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end font-monospace fw-bold fs-6 <?= $isDeath ? 'text-danger' : ($isSale ? 'text-success' : 'text-dark') ?>">
                                        <?= ($isDeath || $isSale) ? '-' : '+' ?><?= number_format($qty) ?>
                                    </td>
                                    <td class="small text-muted text-truncate" style="max-width: 200px;">
                                        <?= e($e['remarks'] ?: 'Normal flock cycle') ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="<?= url("/flocks/{$e['flock_id']}") ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-0.5" style="font-size: 0.72rem;">
                                            Batch &rarr;
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Events Pagination -->
            <?php if (!empty($totalPages) && $totalPages > 1): ?>
                <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center p-3">
                    <span class="text-muted small">
                        Showing <?= count($events) ?> of <?= number_format($totalRows) ?> records (Page <?= $page ?> of <?= $totalPages ?>)
                    </span>
                    <nav aria-label="Flock events navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= url('/flocks?tab=events&page=' . ($page - 1) . '&q=' . urlencode($search)) ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            <li class="page-item active"><span class="page-link"><?= $page ?> / <?= $totalPages ?></span></li>
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= url('/flocks?tab=events&page=' . ($page + 1) . '&q=' . urlencode($search)) ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- BREED POPULATION SUMMARY (Across All Batches) -->
        <?php if (!empty($breedStats)): ?>
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark fs-6">
                        <i class="bi bi-pie-chart-fill text-success me-1"></i>Total Bird Population by Breed & Species
                    </span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                        <?= count($breedStats) ?> Breeds Tracked
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase font-monospace" style="font-size: 0.72rem;">
                            <tr>
                                <th class="ps-3 py-2.5">Breed</th>
                                <th class="py-2.5">Species</th>
                                <th class="text-center py-2.5">Active Batches</th>
                                <th class="text-end py-2.5">Initial Stock</th>
                                <th class="text-end py-2.5">Total Sold</th>
                                <th class="text-end py-2.5">Total Loss</th>
                                <th class="text-end pe-3 py-2.5">Total Current Live Birds</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($breedStats as $bs): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-dark">
                                        <?= e($bs['breed_name']) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= e($bs['species_name']) ?></span>
                                    </td>
                                    <td class="text-center font-monospace">
                                        <span class="badge <?= (int)$bs['active_batches'] > 0 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted border' ?>">
                                            <?= number_format((int)$bs['active_batches']) ?> / <?= number_format((int)$bs['total_batches']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end font-monospace text-muted small">
                                        <?= number_format((int)$bs['total_initial']) ?>
                                    </td>
                                    <td class="text-end font-monospace text-info fw-semibold">
                                        <?= number_format((int)$bs['total_sold']) ?>
                                    </td>
                                    <td class="text-end font-monospace text-danger small">
                                        <?= number_format((int)$bs['total_deaths']) ?>
                                    </td>
                                    <td class="text-end pe-3 font-monospace">
                                        <span class="fw-bold fs-6 <?= (int)$bs['total_current_birds'] > 0 ? 'text-success' : 'text-secondary' ?>">
                                            <?= number_format((int)$bs['total_current_birds']) ?> <small class="fw-normal text-muted" style="font-size: 0.72rem;">birds</small>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- BATCHES TAB (Clean Table with Pagination) -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
            <form method="GET" action="<?= url('/flocks') ?>" class="row g-2 align-items-center">
                <input type="hidden" name="tab" value="batches">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="<?= e($search ?? '') ?>" class="form-control form-control-sm border-start-0" placeholder="Search batch code or breed...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select form-select-sm rounded-3">
                        <option value="all" <?= ($statusFilter ?? '') === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="active" <?= ($statusFilter ?? '') === 'active' ? 'selected' : '' ?>>Active (Stock > 0)</option>
                        <option value="closed" <?= ($statusFilter ?? '') === 'closed' ? 'selected' : '' ?>>Closed / Depleted (Stock = 0)</option>
                    </select>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 fw-bold"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <?php if (!empty($search) || ($statusFilter ?? 'all') !== 'all'): ?>
                        <a href="<?= url('/flocks') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-2.5">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark fs-6"><i class="bi bi-grid-3x3-gap me-1 text-success"></i>Flock Batch Directory</span>
                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                    Showing <?= count($flocks) ?> of <?= number_format($totalRows ?? count($flocks)) ?> Batches
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase font-monospace" style="font-size: 0.72rem;">
                        <tr>
                            <th class="ps-3 py-3">Batch Code</th>
                            <th class="py-3">Breed / Species</th>
                            <th class="py-3" style="width: 110px;">Age</th>
                            <th class="py-3" style="width: 115px;">Intake Date</th>
                            <th class="text-end py-3" style="width: 105px;">Initial Stock</th>
                            <th class="text-end py-3" style="width: 115px;">Deaths (Loss)</th>
                            <th class="text-end py-3" style="width: 130px;">Live Stock</th>
                            <th class="py-3" style="width: 95px;">Status</th>
                            <th class="text-end pe-3 py-3" style="width: 145px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($flocks)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    No flock batches found matching the filter criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($flocks as $f): ?>
                                <?php
                                $stock = (int) $f['current_stock'];
                                $initial = (int) $f['starting_count'];
                                $deaths = (int) $f['deaths_todate'];
                                $mortalityRate = $initial > 0 ? round(($deaths / $initial) * 100, 1) : 0;
                                $isActive = ($stock > 0);
                                ?>
                                <tr>
                                    <td class="ps-3">
                                        <a href="<?= url("/flocks/{$f['flock_id']}") ?>" class="fw-bold text-dark text-decoration-none font-monospace fs-6">
                                            <?= e($f['batch_code']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= e($f['breed_name']) ?></span>
                                    </td>
                                    <td class="font-monospace">
                                        <span class="fw-bold text-dark"><?= (int)$f['age_weeks'] ?> wks</span>
                                        <small class="text-muted d-block" style="font-size: 0.7rem;"><?= (int)$f['age_days'] ?> days</small>
                                    </td>
                                    <td class="font-monospace small text-muted">
                                        <?= !empty($f['start_date']) ? date('d-M-Y', strtotime($f['start_date'])) : '—' ?>
                                    </td>
                                    <td class="text-end font-monospace text-muted small">
                                        <?= number_format($initial) ?>
                                    </td>
                                    <td class="text-end font-monospace">
                                        <span class="<?= $mortalityRate >= 5.0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                            <?= number_format($deaths) ?> <small>(<?= $mortalityRate ?>%)</small>
                                        </span>
                                    </td>
                                    <td class="text-end font-monospace">
                                        <span class="fw-bold fs-6 <?= $isActive ? 'text-success' : 'text-secondary' ?>">
                                            <?= number_format($stock) ?> <small class="fw-normal text-muted" style="font-size: 0.72rem;">birds</small>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border">Depleted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-inline-flex gap-1">
                                            <?php if (\App\Core\Auth::can('flocks', 'edit')): ?>
                                                <a href="<?= url("/flocks/{$f['flock_id']}/edit") ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0.5" style="font-size: 0.72rem;" title="Edit Batch Details">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (\App\Core\Auth::can('flocks', 'create')): ?>
                                                <a href="<?= url('/flocks/quick-entry?flock_id=' . $f['flock_id']) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0.5" style="font-size: 0.72rem;" title="Daily Log">
                                                    <i class="bi bi-plus-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?= url("/flocks/{$f['flock_id']}") ?>" class="btn btn-sm btn-success rounded-pill px-2.5 py-0.5 fw-semibold" style="font-size: 0.72rem;">
                                                View &rarr;
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Batches Pagination -->
            <?php if (!empty($totalPages) && $totalPages > 1): ?>
                <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center p-3">
                    <span class="text-muted small">
                        Showing <?= count($flocks) ?> of <?= number_format($totalRows) ?> batches (Page <?= $page ?> of <?= $totalPages ?>)
                    </span>
                    <nav aria-label="Flock batches navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= url('/flocks?tab=batches&page=' . ($page - 1) . '&q=' . urlencode($search) . '&status=' . urlencode($statusFilter ?? 'all')) ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            <li class="page-item active"><span class="page-link"><?= $page ?> / <?= $totalPages ?></span></li>
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= url('/flocks?tab=batches&page=' . ($page + 1) . '&q=' . urlencode($search) . '&status=' . urlencode($statusFilter ?? 'all')) ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

