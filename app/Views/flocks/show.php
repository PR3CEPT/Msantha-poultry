<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('/flocks') ?>" class="btn btn-sm btn-light border text-muted">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0 text-dark"><?= e($flock['batch_code']) ?></h1>
                <span class="badge <?= (int)$flock['current_stock'] > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-secondary border' ?> fs-6">
                    <?= (int)$flock['current_stock'] > 0 ? 'Active Batch' : 'Closed Batch' ?>
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Breed: <strong><?= e($flock['breed_name']) ?></strong>
                &middot; Intake Date: <?= date('d-M-Y', strtotime($flock['start_date'])) ?>
                &middot; Initial: <?= number_format((int)$flock['starting_count']) ?> chicks
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if (\App\Core\Auth::can('flocks', 'edit')): ?>
                <a href="<?= url("/flocks/{$flock['flock_id']}/edit") ?>" class="btn btn-outline-secondary fw-semibold">
                    <i class="bi bi-pencil me-1"></i>Edit Batch Details
                </a>
            <?php endif; ?>
            <a href="<?= url("/flocks/quick-entry?flock_id={$flock['flock_id']}") ?>" class="btn btn-primary fw-bold">
                <i class="bi bi-pencil-square me-1"></i>Quick Daily Entry
            </a>
            <a href="<?= url("/flocks/price-ladder?breed_id={$flock['breed_id']}") ?>" class="btn btn-outline-secondary">
                <i class="bi bi-ladder me-1"></i>Price Ladder
            </a>
            <?php if ((int)$flock['current_stock'] > 0): ?>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#closeFlockModal">
                    <i class="bi bi-x-circle me-1"></i>Close Batch
                </button>
            <?php endif; ?>
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

    <!-- KPI Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                <span class="text-muted small fw-semibold text-uppercase">Current Stock</span>
                <div class="fs-3 fw-bold text-success mt-1 font-monospace"><?= number_format((int)$flock['current_stock']) ?></div>
                <small class="text-muted">Live birds in pen</small>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-info">
                <span class="text-muted small fw-semibold text-uppercase">Computed Age</span>
                <div class="fs-3 fw-bold text-info mt-1 font-monospace"><?= $age['weeks'] ?> <small class="fs-6 text-muted font-sans-serif">Weeks</small></div>
                <small class="text-muted font-monospace"><?= $age['days'] ?> Days &middot; <?= $age['months'] ?> Mths</small>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-danger">
                <span class="text-muted small fw-semibold text-uppercase">Mortality To Date</span>
                <div class="fs-3 fw-bold text-danger mt-1 font-monospace"><?= number_format((int)$flock['deaths_todate']) ?></div>
                <small class="text-muted">Mortality: <?= (int)$flock['starting_count'] > 0 ? round(((int)$flock['deaths_todate'] / (int)$flock['starting_count']) * 100, 1) : 0 ?>%</small>
            </div>
        </div>
        <div class="col-6 col-md">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary">
                <span class="text-muted small fw-semibold text-uppercase">Birds Sold / Offload</span>
                <div class="fs-3 fw-bold text-primary mt-1 font-monospace"><?= number_format((int)($flock['sold_todate'] ?? $flock['sold_count'] ?? 0)) ?></div>
                <small class="text-muted">Commercial offloads</small>
            </div>
        </div>
        <div class="col-12 col-md">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-warning">
                <span class="text-muted small fw-semibold text-uppercase">Estimated Price / Bird</span>
                <div class="fs-3 fw-bold text-dark mt-1 font-monospace"><?= number_format($estPrice) ?> <small class="fs-6 text-muted font-sans-serif">MWK</small></div>
                <small class="text-muted">Age-ladder price</small>
            </div>
        </div>
    </div>

    <!-- Chart: Stock and Cumulative Deaths & Sold Trajectory -->
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 text-dark fs-6">
                <i class="bi bi-graph-up text-primary me-2"></i>Stock Balance, Cumulative Deaths & Offload Sales Trajectory
            </h5>
            <span class="badge bg-light text-muted border">Daily Dynamics</span>
        </div>
        <div style="height: 280px; position: relative;">
            <canvas id="flockTrajectoryChart"></canvas>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Vaccination Due List -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark fs-6">
                        <i class="bi bi-shield-plus text-info me-2"></i>Vaccination Schedule
                    </h5>
                    <span class="badge bg-light text-muted border">Timeline</span>
                </div>
                <div class="card-body p-2">
                    <?php if (empty($vaccinations)): ?>
                        <div class="text-center py-4 text-muted small">No vaccination template configured.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($vaccinations as $v): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-2 border-0 rounded-2 mb-1 <?= $v['administered'] ? 'bg-success-subtle' : ($v['is_overdue'] ? 'bg-danger-subtle' : ($v['is_due_soon'] ? 'bg-warning-subtle' : '')) ?>">
                                    <div>
                                        <div class="fw-semibold text-dark small"><?= e($v['vaccine']) ?></div>
                                        <small class="text-muted">Day <?= $v['at_day'] ?> &middot; Due: <?= date('d-M-Y', strtotime($v['due_date'])) ?></small>
                                    </div>
                                    <div>
                                        <?php if ($v['administered']): ?>
                                            <span class="badge bg-success"><i class="bi bi-check2 me-1"></i>Done</span>
                                        <?php elseif ($v['is_overdue']): ?>
                                            <span class="badge bg-danger"><i class="bi bi-exclamation-circle me-1"></i>Overdue</span>
                                        <?php elseif ($v['is_due_soon']): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Due Soon</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border">Scheduled</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Events Timeline / Daily Dynamics Section -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark fs-6">
                        <i class="bi bi-clock-history text-secondary me-2"></i>Daily Dynamics & Movement Log
                    </h5>
                    <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                        Showing <?= count($events) ?> Events
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 font-monospace small">
                        <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem;">
                            <tr>
                                <th class="ps-3 py-3" style="font-family: inherit;">Date</th>
                                <th class="py-3" style="font-family: inherit;">Event Type</th>
                                <th class="text-end py-3">Quantity</th>
                                <th class="text-end py-3">Price / Total</th>
                                <th class="pe-3 py-3" style="font-family: inherit;">Customer / Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($events)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted font-sans-serif">No movements or events recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($events as $ev): ?>
                                    <?php
                                    $isDeath = ($ev['event_type'] === 'death');
                                    $isSale = ($ev['event_type'] === 'sale');
                                    $qty = (int)$ev['quantity'];
                                    $amt = (int)($ev['amount'] ?? 0);
                                    $unitPrice = ($qty > 0 && $amt > 0) ? round($amt / $qty) : 0;
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold font-monospace"><?= date('d-M-Y', strtotime($ev['event_date'])) ?></td>
                                        <td class="font-sans-serif">
                                            <?php if ($isDeath): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">DEATH</span>
                                            <?php elseif ($isSale): ?>
                                                <span class="badge bg-success text-white"><i class="bi bi-cart-check me-1"></i>SOLD</span>
                                            <?php elseif ($ev['event_type'] === 'vaccination'): ?>
                                                <span class="badge bg-info-subtle text-info border border-info-subtle">VACCINE</span>
                                            <?php elseif ($ev['event_type'] === 'addition'): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">ADDITION</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border"><?= strtoupper(e($ev['event_type'])) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold font-monospace <?= $isDeath ? 'text-danger' : ($isSale ? 'text-success' : 'text-dark') ?>">
                                            <?php if ($qty > 0): ?>
                                                <?= $isDeath ? '-' : ($isSale ? '-' : '+') ?><?= number_format($qty) ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end font-monospace">
                                            <?php if ($amt > 0): ?>
                                                <div class="fw-bold text-success">+<?= number_format($amt) ?> MWK</div>
                                                <?php if ($unitPrice > 0): ?>
                                                    <small class="text-muted" style="font-size: 0.68rem;">@ <?= number_format($unitPrice) ?>/bird</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-3 small font-sans-serif">
                                            <?php if (!empty($ev['customer_name'])): ?>
                                                <div class="fw-bold text-dark"><i class="bi bi-person me-1"></i><?= e($ev['customer_name']) ?></div>
                                            <?php endif; ?>
                                            <div class="text-muted"><?= e($ev['remarks'] ?: ($isSale ? 'Commercial flock offload' : '—')) ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<!-- Close Batch Modal -->
<div class="modal fade" id="closeFlockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url("/flocks/{$flock['flock_id']}/close") ?>" onsubmit="return confirm('Are you sure you want to close this flock batch?');">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Close Flock Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Closing this batch marks it as depleted / finalized. All historical dynamic entries and events are preserved for reporting.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Closing Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Reason for closing (e.g. all birds sold, cull complete)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold">Close Batch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Chart.js Setup -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('flockTrajectoryChart').getContext('2d');
    const dates = <?= json_encode($trajectory['dates']) ?>;
    const stockData = <?= json_encode($trajectory['stock']) ?>;
    const deathsData = <?= json_encode($trajectory['cumulative_deaths']) ?>;
    const soldData = <?= json_encode($trajectory['cumulative_sold'] ?? []) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [
                {
                    label: 'Live Stock Balance',
                    data: stockData,
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22, 163, 74, 0.08)',
                    fill: true,
                    tension: 0.2,
                    pointRadius: 2,
                    borderWidth: 2,
                    yAxisID: 'y'
                },
                {
                    label: 'Cumulative Deaths',
                    data: deathsData,
                    borderColor: '#dc2626',
                    backgroundColor: 'transparent',
                    borderDash: [4, 4],
                    tension: 0.2,
                    pointRadius: 1,
                    borderWidth: 1.5,
                    yAxisID: 'y'
                },
                {
                    label: 'Cumulative Birds Sold',
                    data: soldData,
                    borderColor: '#0284c7',
                    backgroundColor: 'transparent',
                    borderDash: [2, 2],
                    tension: 0.2,
                    pointRadius: 2,
                    borderWidth: 2,
                    yAxisID: 'y'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, font: { size: 12 } } },
                tooltip: { padding: 10 }
            },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 12, font: { size: 10 } } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 } } }
            }
        }
    });
});
</script>

