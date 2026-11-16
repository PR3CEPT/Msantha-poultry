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

        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark fs-6"><i class="bi bi-egg me-1 text-warning"></i>Daily Egg Harvest Log</span>
            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                Showing <?= count($records) ?> of <?= number_format($totalRows ?? count($records)) ?>
            </span>
        </div>

        <!-- Egg Harvest Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase font-monospace" style="font-size: 0.72rem;">
                    <tr>
                        <th class="ps-3 py-3" style="width: 120px;">Date</th>
                        <th class="py-3">Breed / Batch</th>
                        <th class="text-end py-3" style="width: 130px;">Harvested</th>
                        <th class="text-center py-3" style="width: 140px;">Standard Trays</th>
                        <th class="text-end py-3" style="width: 110px;">Incubated</th>
                        <th class="text-end py-3" style="width: 110px;">Consumed</th>
                        <th class="text-end py-3" style="width: 100px;">Broken</th>
                        <th class="py-3">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                No egg collection records found matching your criteria.
                            </td>
                        </tr>
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
                            <tr>
                                <td class="ps-3 font-monospace small text-dark fw-semibold">
                                    <?= date('d-M-Y', strtotime($r['prod_date'])) ?>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark"><?= e($r['breed_name'] ?? 'General') ?></span>
                                    <?php if (!empty($r['batch_code'])): ?>
                                        <span class="badge bg-light text-dark border ms-1"><?= e($r['batch_code']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark fs-6">
                                    <?= number_format($collected) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                        <?= number_format($trays) ?> trays <?= $loose > 0 ? "+ {$loose}" : '' ?>
                                    </span>
                                </td>
                                <td class="text-end font-monospace text-success fw-semibold">
                                    <?= number_format($incubated) ?>
                                </td>
                                <td class="text-end font-monospace text-info">
                                    <?= number_format($consumed) ?>
                                </td>
                                <td class="text-end font-monospace">
                                    <?php if ($broken > 0): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><?= number_format($broken) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted text-truncate" style="max-width: 180px;">
                                    <?= e($r['remarks'] ?: '—') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (!empty($totalPages) && $totalPages > 1): ?>
            <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center p-3">
                <span class="text-muted small">
                    Showing <?= count($records) ?> of <?= number_format($totalRows) ?> records (Page <?= $page ?> of <?= $totalPages ?>)
                </span>
                <nav aria-label="Egg records navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('/eggs?page=' . ($page - 1) . '&q=' . urlencode($search) . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate)) ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        <li class="page-item active"><span class="page-link"><?= $page ?> / <?= $totalPages ?></span></li>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('/eggs?page=' . ($page + 1) . '&q=' . urlencode($search) . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate)) ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

