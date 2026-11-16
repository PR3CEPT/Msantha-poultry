<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Header Section -->
    <div
        class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 fw-bold mb-0 text-dark">Hatchery & Incubation Services</h1>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
                    <?= number_format($stats['total_batches']) ?> Batches
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Batch tracking, customer egg setting, candling, hatch percentage
                monitoring, and fee settlements.</p>
        </div>
        <div class="d-flex gap-2">
            <?php if (\App\Core\Auth::can('incubation', 'create')): ?>
            <a href="<?= url('/incubation/receive') ?>" class="btn btn-success fw-bold">
                <i class="bi bi-plus-circle me-1"></i>Receive Eggs
            </a>
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

    <!-- KPI Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-primary">
                <span class="text-muted small fw-semibold text-uppercase">Eggs Incubated</span>
                <div class="fs-3 fw-bold text-primary mt-1"><?= number_format($stats['total_eggs']) ?></div>
                <small class="text-muted">Total set across batches</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-success">
                <span class="text-muted small fw-semibold text-uppercase">Chicks Hatched</span>
                <div class="fs-3 fw-bold text-success mt-1"><?= number_format($stats['total_hatched']) ?></div>
                <small class="text-muted">Hatch Rate: <strong><?= $stats['avg_hatch_rate'] ?>%</strong></small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-info">
                <span class="text-muted small fw-semibold text-uppercase">Total Invoiced</span>
                <div class="fs-3 fw-bold text-dark mt-1"><?= number_format($stats['total_cost']) ?> <small
                        class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted">Collected: <?= number_format($stats['total_paid']) ?> MWK</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-danger">
                <span class="text-muted small fw-semibold text-uppercase">Outstanding Balance</span>
                <div class="fs-3 fw-bold text-danger mt-1"><?= number_format($stats['total_balance']) ?> <small
                        class="fs-6 text-muted">MWK</small></div>
                <small class="text-muted">Customer receivables</small>
            </div>
        </div>
    </div>

    <!-- 3 Dashboard Action Widgets (Prompt 5 requirement) -->
    <div class="row g-3 mb-4">
        <!-- Widget 1: Transfer Today -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark small text-uppercase">
                        <i class="bi bi-arrow-left-right text-warning me-1"></i>Transfer to Hatcher Today
                    </h6>
                    <span
                        class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><?= count($transferToday) ?>
                        Due</span>
                </div>
                <div class="card-body p-2">
                    <?php if (empty($transferToday)): ?>
                        <div class="text-center py-3 text-muted small">No batches due for transfer today.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($transferToday as $t): ?>
                                <a href="<?= url("/incubation-jobs/{$t['id']}") ?>"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-2 border-0 rounded-2 mb-1">
                                    <div>
                                        <div class="fw-semibold text-dark small">Batch #<?= $t['id'] ?> &middot;
                                            <?= e($t['customer_name']) ?></div>
                                        <small class="text-muted"><?= number_format((int) $t['eggs_incubated']) ?> eggs</small>
                                    </div>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-chevron-right"></i></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Widget 2: Hatching Soon -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark small text-uppercase">
                        <i class="bi bi-egg-fried text-success me-1"></i>Hatching Soon (Next 3 Days)
                    </h6>
                    <span
                        class="badge bg-success-subtle text-success border border-success-subtle"><?= count($hatchingSoon) ?></span>
                </div>
                <div class="card-body p-2">
                    <?php if (empty($hatchingSoon)): ?>
                        <div class="text-center py-3 text-muted small">No hatches scheduled in the next 3 days.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($hatchingSoon as $h): ?>
                                <a href="<?= url("/incubation-jobs/{$h['id']}") ?>"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-2 border-0 rounded-2 mb-1">
                                    <div>
                                        <div class="fw-semibold text-dark small">Batch #<?= $h['id'] ?> &middot;
                                            <?= e($h['customer_name']) ?></div>
                                        <small class="text-muted">Due: <?= date('d-M', strtotime($h['expected_hatch_date'])) ?>
                                            &middot; <?= number_format((int) $h['eggs_incubated']) ?> eggs</small>
                                    </div>
                                    <span class="badge bg-success-subtle text-success border"><i
                                            class="bi bi-chevron-right"></i></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Widget 3: Unpaid Balances -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark small text-uppercase">
                        <i class="bi bi-cash-coin text-danger me-1"></i>Top Unpaid Balances
                    </h6>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Debts</span>
                </div>
                <div class="card-body p-2">
                    <?php if (empty($unpaidBalances)): ?>
                        <div class="text-center py-3 text-muted small">All incubation accounts settled.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($unpaidBalances as $u): ?>
                                <a href="<?= url("/incubation-jobs/{$u['id']}") ?>"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-2 border-0 rounded-2 mb-1">
                                    <div>
                                        <div class="fw-semibold text-dark small"><?= e($u['customer_name']) ?></div>
                                        <small class="text-muted">Batch #<?= $u['id'] ?> &middot;
                                            <?= e($u['customer_phone'] ?? 'No phone') ?></small>
                                    </div>
                                    <span class="text-danger fw-bold small"><?= number_format((int) $u['balance']) ?> MWK</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom p-3">
            <form method="GET" action="<?= url('/incubation-jobs') ?>" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="<?= e($search ?? '') ?>" class="form-control"
                            placeholder="Search customer, phone, or batch ref...">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" name="date_from" value="<?= e($dateFrom ?? '') ?>" class="form-control form-control-sm"
                        title="Received From Date" placeholder="From Date">
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" name="date_to" value="<?= e($dateTo ?? '') ?>" class="form-control form-control-sm"
                        title="Received To Date" placeholder="To Date">
                </div>
                <div class="col-8 col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="all" <?= ($status ?? 'all') === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="incubating" <?= ($status ?? '') === 'incubating' ? 'selected' : '' ?>>Incubating (In
                            Setter)</option>
                        <option value="hatching" <?= ($status ?? '') === 'hatching' ? 'selected' : '' ?>>Hatching (In
                            Hatcher)</option>
                        <option value="completed" <?= ($status ?? '') === 'completed' ? 'selected' : '' ?>>Completed
                        </option>
                        <option value="received" <?= ($status ?? '') === 'received' ? 'selected' : '' ?>>Received</option>
                        <option value="cancelled" <?= ($status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled
                        </option>
                    </select>
                </div>
                <div class="col-4 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-filter me-1"></i>Filter</button>
                    <?php if (!empty($search) || ($status ?? 'all') !== 'all' || !empty($dateFrom) || !empty($dateTo)): ?>
                        <a href="<?= url('/incubation-jobs') ?>" class="btn btn-sm btn-outline-secondary" title="Reset">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Incubation Batches Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Batch #</th>
                        <th>Intake Date</th>
                        <th>Customer</th>
                        <th>Breed</th>
                        <th class="text-end">Eggs Set</th>
                        <th class="text-end">Hatched</th>
                        <th class="text-center">Hatch %</th>
                        <th>Hatch Date</th>
                        <th class="text-end">Balance Due</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jobs)): ?>
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                <span class="fw-bold text-dark d-block">No Incubation Batches Found</span>
                                <span class="small">No records match the filter criteria.</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($jobs as $j): ?>
                            <?php
                            $eggs = (int) $j['eggs_incubated'];
                            $hatched = (int) $j['hatched'];
                            $hatchPct = !empty($j['hatch_percent']) ? (float) $j['hatch_percent'] : ($eggs > 0 ? round(($hatched / $eggs) * 100, 1) : 0);
                            $isOwnerBatch = !empty($j['customer_is_internal']) || stripos((string) ($j['customer_name'] ?? ''), 'msantha') !== false;
                            $bal = $isOwnerBatch ? 0 : (int) $j['balance'];
                            $jobStatus = strtolower((string) $j['status']);
                            $statusBadge = match ($jobStatus) {
                                'incubating' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                'hatching' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                'completed' => 'bg-success-subtle text-success border border-success-subtle',
                                'cancelled' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                default => 'bg-light text-secondary border'
                            };
                            ?>
                            <tr>
                                <td class="ps-3 font-monospace fw-bold text-dark">
                                    #<?= e((string) $j['id']) ?>
                                </td>
                                <td class="text-nowrap small text-muted font-monospace">
                                    <?= !empty($j['date_received']) ? date('d-M-Y', strtotime($j['date_received'])) : '—' ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($j['customer_name'] ?? 'Walk-In Customer') ?></div>
                                    <?php if ($isOwnerBatch): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">
                                            Owner / Complimentary
                                        </span>
                                    <?php elseif (!empty($j['customer_phone'])): ?>
                                        <span class="text-muted small font-monospace"><?= e($j['customer_phone']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= e($j['breed_name'] ?? 'Local/Mixed') ?>
                                </td>
                                <td class="text-end font-monospace fw-semibold text-dark">
                                    <?= number_format($eggs) ?>
                                </td>
                                <td class="text-end font-monospace fw-semibold text-success">
                                    <?= number_format($hatched) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($hatchPct > 0): ?>
                                        <span class="badge <?= $hatchPct >= 65 ? 'bg-success text-white' : 'bg-warning text-dark' ?> fw-bold">
                                            <?= $hatchPct ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap small text-muted font-monospace">
                                    <?= !empty($j['date_hatched']) ? date('d-M-Y', strtotime($j['date_hatched'])) : (!empty($j['expected_hatch_date']) ? date('d-M-Y', strtotime($j['expected_hatch_date'])) : 'Pending') ?>
                                </td>
                                <td class="text-end font-monospace">
                                    <?php if ($isOwnerBatch): ?>
                                        <span class="text-success small fw-semibold">0 (Owner)</span>
                                    <?php elseif ($bal > 0): ?>
                                        <span class="text-danger fw-bold"><?= number_format($bal) ?> MWK</span>
                                    <?php else: ?>
                                        <span class="text-success small fw-semibold">Paid</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $statusBadge ?> fw-semibold px-2 py-1">
                                        <?= strtoupper($jobStatus) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3 text-nowrap">
                                    <a href="<?= url("/incubation-jobs/{$j['id']}") ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1" title="View Batch Details">
                                        <i class="bi bi-eye me-1"></i>View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center p-3">
                <span class="text-muted small">
                    Showing page <?= $page ?> of <?= $totalPages ?> (<?= number_format($totalRows) ?> total batches)
                </span>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="<?= url('/incubation-jobs?page=' . ($page - 1) . '&status=' . urlencode($status) . '&q=' . urlencode($search)) ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        <li class="page-item active">
                            <span class="page-link"><?= $page ?></span>
                        </li>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="<?= url('/incubation-jobs?page=' . ($page + 1) . '&status=' . urlencode($status) . '&q=' . urlencode($search)) ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>