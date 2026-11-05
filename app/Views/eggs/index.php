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
            <a href="<?= url('/eggs/create') ?>" class="btn btn-warning fw-bold text-dark">
                <i class="bi bi-plus-circle me-1"></i>Record Collection
            </a>
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

        <!-- Table View -->
        <div class="table-responsive d-none d-md-block">
            <table class="table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3 py-3" style="width: 140px;">Collection Date</th>
                        <th class="py-3">Breed / Flock</th>
                        <th class="text-end py-3" style="width: 140px;">Eggs Harvested</th>
                        <th class="text-end py-3" style="width: 130px;">Trays (30/tray)</th>
                        <th class="text-end py-3" style="width: 110px;">Broken</th>
                        <th class="text-end py-3" style="width: 110px;">Consumed</th>
                        <th class="text-end pe-3 py-3" style="width: 130px;">Hatchery Inflow</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                No egg collection records found matching criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $r): ?>
                            <?php
                            $collected = (int) $r['collected'];
                            $trays = floor($collected / 30);
                            $broken = (int) $r['broken'];
                            ?>
                            <tr>
                                <td class="ps-3 py-2.5 font-monospace fw-bold text-dark small">
                                    <?= date('d-M-Y', strtotime($r['prod_date'])) ?>
                                </td>
                                <td class="py-2.5">
                                    <span class="badge bg-light text-dark border">
                                        <?= e($r['breed_name'] ?? $r['batch_code'] ?? 'Quails / Mixed') ?>
                                    </span>
                                </td>
                                <td class="text-end py-2.5 fw-bold text-dark fs-6">
                                    <?= number_format($collected) ?>
                                </td>
                                <td class="text-end py-2.5 fw-semibold text-warning-emphasis">
                                    <?= number_format($trays) ?> <small class="text-muted" style="font-size: 0.7rem;">trays</small>
                                </td>
                                <td class="text-end py-2.5">
                                    <span class="<?= $broken > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>">
                                        <?= number_format($broken) ?>
                                    </span>
                                </td>
                                <td class="text-end py-2.5 text-muted">
                                    <?= number_format((int)$r['consumed']) ?>
                                </td>
                                <td class="text-end pe-3 py-2.5">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">
                                        <?= number_format((int)$r['incubated']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View (< 768px) -->
        <div class="d-md-none p-3">
            <?php if (empty($records)): ?>
                <div class="text-center py-4 text-muted">No egg records found.</div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($records as $r): ?>
                        <div class="card border rounded-3 p-3 shadow-sm bg-white">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-bold text-dark"><?= date('d-M-Y', strtotime($r['prod_date'])) ?></div>
                                    <small class="text-muted"><?= e($r['breed_name'] ?? 'Quail / Flock') ?></small>
                                </div>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                    <?= floor((int)$r['collected'] / 30) ?> Trays
                                </span>
                            </div>
                            <div class="row g-2 text-center py-2 bg-light rounded-2 my-1">
                                <div class="col-4">
                                    <small class="text-muted d-block">Collected</small>
                                    <span class="fw-bold text-dark fs-6"><?= number_format((int)$r['collected']) ?></span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Broken</small>
                                    <span class="fw-semibold text-danger"><?= number_format((int)$r['broken']) ?></span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Incubated</small>
                                    <span class="fw-semibold text-success"><?= number_format((int)$r['incubated']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

