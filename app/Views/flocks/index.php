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
        <!-- EVENTS TAB (Z-Fold) -->
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

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark fs-6"><i class="bi bi-heart-pulse me-1 text-danger"></i>Mortality & Dynamics Event Log</span>
                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                    <?= count($events) ?> Events
                </span>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold shadow-sm" data-zfold-toggle-all="#flockEventsContainer">
                <i class="bi bi-chevron-bar-expand me-1"></i>Unfold All
            </button>
        </div>

        <div class="zfold-container mb-4" id="flockEventsContainer">
            <?php if (empty($events)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted bg-white">
                    <i class="bi bi-heart-pulse display-6 d-block mb-2 text-muted"></i>
                    <h6 class="fw-bold text-dark">No Flock Events Recorded</h6>
                    <p class="small text-muted mb-0">No mortality or offload entries found matching criteria.</p>
                </div>
            <?php else: ?>
                <?php foreach ($events as $e): ?>
                    <?php
                    $isDeath = ($e['event_type'] === 'death');
                    $isSale = ($e['event_type'] === 'sale');
                    $qty = (int) $e['quantity'];
                    ?>
                    <div class="zfold-card mb-2.5 <?= $isDeath ? 'border-start border-4 border-danger' : ($isSale ? 'border-start border-4 border-success' : '') ?>">
                        <div class="zfold-header">
                            <div class="zfold-tier-top">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="fw-bold font-monospace text-dark small" style="font-size: 0.82rem;">
                                        <i class="bi bi-calendar3 me-1 text-muted"></i><?= date('d-M-Y', strtotime($e['event_date'])) ?>
                                    </span>
                                    <span class="badge bg-light text-dark border fw-bold" style="font-size: 0.72rem;">
                                        <?= e($e['batch_code']) ?>
                                    </span>
                                    <span class="text-muted small"><?= e($e['breed_name']) ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($isDeath): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold fs-6 px-2.5 py-1">
                                            -<?= number_format($qty) ?> Deaths
                                        </span>
                                    <?php elseif ($isSale): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold fs-6 px-2.5 py-1">
                                            +<?= number_format($qty) ?> Sold
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary fw-bold fs-6 px-2.5 py-1">
                                            <?= number_format($qty) ?> <?= strtoupper(e($e['event_type'])) ?>
                                        </span>
                                    <?php endif; ?>
                                    <button type="button" class="zfold-toggle-btn" aria-label="Toggle event details">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="zfold-tier-bottom mt-1">
                                <span class="text-muted small text-truncate" style="max-width: 70%;">
                                    <?= !empty($e['remarks']) ? e($e['remarks']) : 'No specific remarks entered' ?>
                                </span>
                                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.68rem;">
                                    Event ID #<?= e((string)$e['id']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="zfold-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="p-3 bg-white rounded-3 border h-100">
                                        <strong class="d-block small text-dark mb-1">Flock Context</strong>
                                        <div class="small text-muted mb-1">Batch Code: <strong class="text-dark"><?= e($e['batch_code']) ?></strong></div>
                                        <div class="small text-muted mb-1">Breed & Species: <span class="badge bg-light text-dark border"><?= e($e['breed_name']) ?></span></div>
                                        <div class="small text-muted">Event Date: <?= date('d-F-Y', strtotime($e['event_date'])) ?></div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="p-3 bg-white rounded-3 border h-100 d-flex flex-column justify-content-between">
                                        <div>
                                            <strong class="d-block small text-dark mb-1">Remarks & Cause</strong>
                                            <p class="small text-muted mb-2 font-monospace"><?= e($e['remarks'] ?? 'Normal flock event cycle.') ?></p>
                                        </div>
                                        <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                                            <a href="<?= url("/flocks/{$e['flock_id']}") ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3" style="font-size: 0.75rem;">
                                                View Batch Lifecycle &rarr;
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- BATCHES TAB (Z-Fold) -->
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

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark fs-6"><i class="bi bi-grid-3x3-gap me-1 text-success"></i>Flock Batch Directory</span>
                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                    <?= count($flocks) ?> Batches Tracked
                </span>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold shadow-sm" data-zfold-toggle-all="#flockBatchesContainer">
                <i class="bi bi-chevron-bar-expand me-1"></i>Unfold All
            </button>
        </div>

        <div class="zfold-container mb-4" id="flockBatchesContainer">
            <?php if (empty($flocks)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted bg-white">
                    <i class="bi bi-inbox display-6 d-block mb-2 text-secondary"></i>
                    <h6 class="fw-bold text-dark">No Flock Batches Found</h6>
                    <p class="small text-muted mb-0">No records match the filter criteria.</p>
                </div>
            <?php else: ?>
                <?php foreach ($flocks as $f): ?>
                    <?php
                    $stock = (int) $f['current_stock'];
                    $initial = (int) $f['starting_count'];
                    $deaths = (int) $f['deaths_todate'];
                    $mortalityRate = $initial > 0 ? round(($deaths / $initial) * 100, 1) : 0;
                    $isActive = ($stock > 0);
                    ?>
                    <div class="zfold-card mb-2.5 <?= $isActive ? 'border-start border-4 border-success' : 'border-start border-4 border-secondary' ?>">
                        <div class="zfold-header">
                            <!-- Tier 1: Batch Code & Breed on Left <---> Stock Count & Chevron on Right -->
                            <div class="zfold-tier-top">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="fw-bold text-dark fs-6">
                                        <?= e($f['batch_code']) ?>
                                    </span>
                                    <span class="badge bg-light text-dark border" style="font-size: 0.72rem;">
                                        <?= e($f['breed_name']) ?>
                                    </span>
                                    <?php if ($isActive): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">
                                            <i class="bi bi-check-circle-fill me-1"></i>Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border" style="font-size: 0.65rem;">
                                            Depleted
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-6 fw-bold <?= $isActive ? 'text-success' : 'text-secondary' ?> font-monospace">
                                        <?= number_format($stock) ?> <small class="text-muted fw-normal" style="font-size: 0.72rem;">Birds</small>
                                    </span>
                                    <button type="button" class="zfold-toggle-btn" aria-label="Toggle batch breakdown">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Tier 2: Intake Date on Left <---> Mortality % & Tray Price on Right -->
                            <div class="zfold-tier-bottom mt-1">
                                <span class="text-muted small">
                                    Intake: <strong class="text-dark font-monospace"><?= !empty($f['start_date']) ? date('d-M-Y', strtotime($f['start_date'])) : '—' ?></strong>
                                </span>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge <?= $mortalityRate >= 5.0 ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-light text-muted border' ?>" style="font-size: 0.7rem;">
                                        Mortality: <?= $mortalityRate ?>% (<?= number_format($deaths) ?> deaths)
                                    </span>
                                    <?php if (!empty($f['egg_tray_price'])): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.7rem;">
                                            Tray: <?= number_format((int)$f['egg_tray_price']) ?> MWK
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Z-Fold Body: Detailed Metrics & Actions -->
                        <div class="zfold-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <div class="p-3 bg-white rounded-3 border h-100">
                                        <span class="text-muted small d-block mb-1 text-uppercase fw-semibold" style="font-size: 0.7rem;">Flock Dynamic Counts</span>
                                        <div class="d-flex justify-content-between py-1 border-bottom small">
                                            <span class="text-muted">Initial Stocked:</span>
                                            <span class="fw-semibold text-dark font-monospace"><?= number_format($initial) ?> birds</span>
                                        </div>
                                        <div class="d-flex justify-content-between py-1 border-bottom small">
                                            <span class="text-muted">Total Mortality Deaths:</span>
                                            <span class="fw-semibold text-danger font-monospace">-<?= number_format($deaths) ?> birds</span>
                                        </div>
                                        <div class="d-flex justify-content-between pt-1 small">
                                            <span class="fw-semibold text-dark">Current Live Stock:</span>
                                            <span class="fw-bold <?= $isActive ? 'text-success' : 'text-secondary' ?> font-monospace"><?= number_format($stock) ?> birds</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="p-3 bg-white rounded-3 border h-100">
                                        <span class="text-muted small d-block mb-1 text-uppercase fw-semibold" style="font-size: 0.7rem;">Production & Economics</span>
                                        <div class="small mb-1">Mortality Rate: <strong class="<?= $mortalityRate >= 5.0 ? 'text-danger' : 'text-success' ?>"><?= $mortalityRate ?>%</strong></div>
                                        <div class="small mb-1">Egg Tray Price: <strong class="text-dark"><?= !empty($f['egg_tray_price']) ? number_format((int)$f['egg_tray_price']) . ' MWK' : 'Not set' ?></strong></div>
                                        <div class="small text-muted font-monospace">Batch Reference #<?= e((string)$f['flock_id']) ?></div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="p-3 bg-white rounded-3 border h-100 d-flex flex-column justify-content-between">
                                        <div>
                                            <span class="text-muted small d-block mb-1 text-uppercase fw-semibold" style="font-size: 0.7rem;">Quick Operations</span>
                                            <small class="text-muted d-block mb-2">Record daily mortality, egg yields, or view full batch lifecycle history.</small>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                                            <a href="<?= url('/flocks/quick-entry') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3" style="font-size: 0.75rem;">
                                                <i class="bi bi-pencil-square me-1"></i>Quick Entry
                                            </a>
                                            <a href="<?= url("/flocks/{$f['flock_id']}") ?>" class="btn btn-success btn-sm rounded-pill px-3 fw-bold" style="font-size: 0.75rem;">
                                                Batch Profile &rarr;
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

