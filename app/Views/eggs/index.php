<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 fw-bold mb-0 text-dark">Egg Production & Collections</h1>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-6">
                    <?= number_format($stats['total_trays']) ?> Trays Produced
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Daily egg harvest tracking, tray conversions (30 eggs/tray), breakage monitoring, and incubation allocation.</p>
        </div>
        <div class="d-flex gap-2">
            <?php if (\App\Core\Auth::can('eggs', 'create')): ?>
            <a href="<?= url('/eggs/create') ?>" class="btn btn-warning fw-bold text-dark">
                <i class="bi bi-plus-circle me-1"></i>Record Collection
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-warning">
                <span class="text-muted small fw-semibold text-uppercase">Total Eggs Harvested</span>
                <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($stats['total_collected']) ?></div>
                <small class="text-muted"><?= number_format($stats['total_trays']) ?> Standard Trays</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-danger">
                <span class="text-muted small fw-semibold text-uppercase">Total Broken</span>
                <div class="fs-3 fw-bold text-danger mt-1"><?= number_format($stats['total_broken']) ?></div>
                <small class="text-muted">Breakage Rate: <?= $stats['broken_rate'] ?>%</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-success">
                <span class="text-muted small fw-semibold text-uppercase">Allocated Incubated</span>
                <div class="fs-3 fw-bold text-success mt-1"><?= number_format($stats['total_incubated']) ?></div>
                <small class="text-muted">Direct to Hatchery</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-info">
                <span class="text-muted small fw-semibold text-uppercase">Staff Consumed</span>
                <div class="fs-3 fw-bold text-info mt-1"><?= number_format($stats['total_consumed']) ?></div>
                <small class="text-muted">Farm rations</small>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom p-3">
            <form method="GET" action="<?= url('/eggs') ?>" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="<?= e($search ?? '') ?>" class="form-control" placeholder="Search breed, flock, or notes...">
                    </div>
                </div>
                <div class="col-md-3">
                    <input type="date" name="start_date" value="<?= e($startDate ?? '') ?>" class="form-control" title="From Date">
                </div>
                <div class="col-md-3">
                    <input type="date" name="end_date" value="<?= e($endDate ?? '') ?>" class="form-control" title="To Date">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-filter me-1"></i>Filter</button>
                    <?php if (!empty($search) || !empty($startDate) || !empty($endDate)): ?>
                        <a href="<?= url('/eggs') ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Z-Fold Controls -->
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-white">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark fs-6"><i class="bi bi-egg me-1 text-warning"></i>Daily Egg Harvest Records</span>
                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                    <?= count($records) ?> Harvest Days
                </span>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold shadow-sm" data-zfold-toggle-all="#eggHarvestContainer">
                <i class="bi bi-chevron-bar-expand me-1"></i>Unfold All
            </button>
        </div>

        <!-- Z-Fold Egg Collection Cards -->
        <div class="p-3 zfold-container" id="eggHarvestContainer">
            <?php if (empty($records)): ?>
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                    <h6 class="fw-bold text-dark">No Egg Collection Records Found</h6>
                    <p class="small text-muted mb-0">No harvests recorded matching your search or date criteria.</p>
                </div>
            <?php else: ?>
                <?php foreach ($records as $r): ?>
                    <?php
                    $collected = (int) $r['collected'];
                    $trays = floor($collected / 30);
                    $loose = $collected % 30;
                    $broken = (int) $r['broken'];
                    $consumed = (int) $r['consumed'];
                    $incubated = (int) $r['incubated'];
                    $sold = (int) ($r['sold'] ?? 0);
                    ?>
                    <div class="zfold-card mb-2.5">
                        <div class="zfold-header">
                            <!-- Tier 1: Collection Date & Breed on Left <---> Eggs & Trays on Right -->
                            <div class="zfold-tier-top">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="fw-bold font-monospace text-dark small" style="font-size: 0.82rem;">
                                        <i class="bi bi-calendar3 me-1 text-muted"></i><?= date('d-M-Y', strtotime($r['prod_date'])) ?>
                                    </span>
                                    <span class="badge bg-light text-dark border" style="font-size: 0.72rem;">
                                        <?= e($r['breed_name'] ?? $r['batch_code'] ?? 'Layers / Quails') ?>
                                    </span>
                                    <?php if ($trays > 0): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.68rem;">
                                            <?= number_format($trays) ?> Trays <?= $loose > 0 ? "+ {$loose} eggs" : '' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-6 fw-bold text-dark font-monospace">
                                        <?= number_format($collected) ?> <small class="text-muted fw-normal" style="font-size: 0.72rem;">Eggs</small>
                                    </span>
                                    <button type="button" class="zfold-toggle-btn" aria-label="Toggle collection details">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Tier 2: Allocation Breakdown on Left <---> Broken/Loss on Right -->
                            <div class="zfold-tier-bottom mt-1">
                                <div class="d-flex align-items-center gap-2 text-muted small">
                                    <span>Hatchery: <strong class="text-success font-monospace"><?= number_format($incubated) ?></strong></span>
                                    <span>&bull;</span>
                                    <span>Consumed: <strong class="text-info font-monospace"><?= number_format($consumed) ?></strong></span>
                                    <?php if ($sold > 0): ?>
                                        <span>&bull;</span>
                                        <span>Sold: <strong class="text-primary font-monospace"><?= number_format($sold) ?></strong></span>
                                    <?php endif; ?>
                                </div>
                                <span class="badge <?= $broken > 0 ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-light text-muted border' ?>" style="font-size: 0.7rem;">
                                    Broken: <?= number_format($broken) ?> eggs
                                </span>
                            </div>
                        </div>

                        <!-- Z-Fold Body: Detailed Allocation Breakdown -->
                        <div class="zfold-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <div class="p-3 bg-white rounded-3 border h-100">
                                        <span class="text-muted small d-block mb-1 text-uppercase fw-semibold" style="font-size: 0.7rem;">Tray Conversions</span>
                                        <div class="d-flex justify-content-between py-1 border-bottom small">
                                            <span class="text-muted">Total Gross Collected:</span>
                                            <strong class="text-dark font-monospace"><?= number_format($collected) ?> eggs</strong>
                                        </div>
                                        <div class="d-flex justify-content-between py-1 border-bottom small">
                                            <span class="text-muted">Standard 30-Egg Trays:</span>
                                            <strong class="text-warning-emphasis font-monospace"><?= number_format($trays) ?> trays</strong>
                                        </div>
                                        <div class="d-flex justify-content-between pt-1 small">
                                            <span class="text-muted">Loose Remainder:</span>
                                            <strong class="text-secondary font-monospace"><?= $loose ?> loose eggs</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="p-3 bg-white rounded-3 border h-100">
                                        <span class="text-muted small d-block mb-1 text-uppercase fw-semibold" style="font-size: 0.7rem;">Harvest Dispositions</span>
                                        <div class="small mb-1">To Incubation: <span class="badge bg-success-subtle text-success"><?= number_format($incubated) ?> eggs</span></div>
                                        <div class="small mb-1">Farm / Staff Consumed: <span class="badge bg-info-subtle text-info"><?= number_format($consumed) ?> eggs</span></div>
                                        <div class="small mb-1">Damaged / Broken: <span class="badge bg-danger-subtle text-danger"><?= number_format($broken) ?> eggs</span></div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="p-3 bg-white rounded-3 border h-100 d-flex flex-column justify-content-between">
                                        <div>
                                            <span class="text-muted small d-block mb-1 text-uppercase fw-semibold" style="font-size: 0.7rem;">Notes & Pen Provenance</span>
                                            <p class="small text-muted mb-2 font-monospace">Daily egg recording for flock <?= e($r['batch_code'] ?? 'General') ?>.</p>
                                        </div>
                                        <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                                            <?php if (\App\Core\Auth::can('eggs', 'create')): ?>
                                            <a href="<?= url('/eggs/create') ?>" class="btn btn-outline-warning btn-sm rounded-pill px-3 text-dark fw-bold" style="font-size: 0.75rem;">
                                                <i class="bi bi-plus-circle me-1"></i>New Entry
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

