<?php declare(strict_types=1); ?>
<div class="p-3 p-md-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 fw-bold text-dark mb-0">Data Quality Review</h1>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Audit & Correction</span>
            </div>
            <p class="text-muted small mb-0">Inspect and resolve Excel data anomalies, date typos, missing dates, and projected calendar rows</p>
        </div>
        <a href="<?= url('/admin/import') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-box-arrow-in-down"></i>
            <span>Import Center</span>
        </a>
    </div>

    <!-- Navigation Pills -->
    <ul class="nav nav-pills gap-2 mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'suspect' ? 'active bg-success text-white' : 'bg-white border text-dark' ?>" href="<?= url('/admin/data-review?tab=suspect') ?>">
                <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Suspect Dates
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'missing' ? 'active bg-success text-white' : 'bg-white border text-dark' ?>" href="<?= url('/admin/data-review?tab=missing') ?>">
                <i class="bi bi-calendar-x text-danger me-1"></i>Missing Dates
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'projected' ? 'active bg-success text-white' : 'bg-white border text-dark' ?>" href="<?= url('/admin/data-review?tab=projected') ?>">
                <i class="bi bi-calendar-plus text-primary me-1"></i>Projected Rows
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'skipped' ? 'active bg-success text-white' : 'bg-white border text-dark' ?>" href="<?= url('/admin/data-review?tab=skipped') ?>">
                <i class="bi bi-journal-x text-muted me-1"></i>Skipped / Labels Log
            </a>
        </li>
    </ul>

    <!-- Tab 1: Suspect Dates -->
    <?php if ($activeTab === 'suspect'): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold text-dark mb-0">Flagged Suspect Dates (<code>date_suspect = 1</code>)</h5>
                <span class="badge bg-light text-dark border"><?= count($suspectDates) ?> Items</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($suspectDates)): ?>
                    <div class="state-empty py-5 text-center">
                        <i class="bi bi-check-circle-fill text-success display-4 mb-3 d-block"></i>
                        <h5 class="fw-semibold">All Dates Clean!</h5>
                        <p class="text-muted small">No suspect or out-of-sequence dates are currently flagged.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Source & Context</th>
                                    <th>Excel Row</th>
                                    <th>Flagged Value</th>
                                    <th>Issue Reason</th>
                                    <th style="min-width: 250px;">Corrected Date & Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($suspectDates as $item): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-semibold text-dark"><?= e($item['context']) ?></div>
                                            <small class="text-muted font-monospace"><?= e($item['table']) ?></small>
                                        </td>
                                        <td><span class="badge bg-light text-dark border">Row <?= (int) $item['excel_row'] ?></span></td>
                                        <td>
                                            <span class="text-danger fw-bold font-monospace"><?= e((string) ($item['current_date'] ?: 'NULL / Unparsed')) ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= e($item['reason']) ?></small>
                                        </td>
                                        <td>
                                            <form method="POST" action="<?= url('/admin/data-review/resolve') ?>" class="d-flex align-items-center gap-2 m-0">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="table" value="<?= e($item['table']) ?>">
                                                <input type="hidden" name="record_id" value="<?= (int) $item['id'] ?>">
                                                <input type="hidden" name="return_tab" value="suspect">
                                                <input type="date" name="corrected_date" class="form-control form-control-sm" required value="<?= e((string) ($item['current_date'] ?: '2026-01-01')) ?>" style="max-width: 150px;">
                                                <button type="submit" class="btn btn-sm btn-success fw-bold py-1 px-2 text-nowrap">
                                                    <i class="bi bi-check-lg me-1"></i>Resolve
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tab 2: Missing Dates -->
    <?php if ($activeTab === 'missing'): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold text-dark mb-0">Missing Transaction Dates (<code>date_missing = 1</code>)</h5>
                <span class="badge bg-light text-dark border"><?= count($missingDates) ?> Items</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($missingDates)): ?>
                    <div class="state-empty py-5 text-center">
                        <i class="bi bi-check-circle-fill text-success display-4 mb-3 d-block"></i>
                        <h5 class="fw-semibold">No Missing Dates!</h5>
                        <p class="text-muted small">All cash book rows have valid dates assigned.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Business Unit</th>
                                    <th>Description</th>
                                    <th>Receipt</th>
                                    <th>Expenditure</th>
                                    <th style="min-width: 250px;">Assign Date & Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($missingDates as $m): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold"><?= e($m['business_unit']) ?></td>
                                        <td>
                                            <div class="text-dark"><?= e($m['description']) ?></div>
                                            <small class="text-muted font-monospace"><?= e($m['legacy_ref']) ?></small>
                                        </td>
                                        <td class="text-success fw-semibold"><?= $m['receipt'] > 0 ? '+MK ' . number_format($m['receipt']) : '-' ?></td>
                                        <td class="text-danger fw-semibold"><?= $m['expenditure'] > 0 ? '-MK ' . number_format($m['expenditure']) : '-' ?></td>
                                        <td>
                                            <form method="POST" action="<?= url('/admin/data-review/resolve') ?>" class="d-flex align-items-center gap-2 m-0">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="table" value="cash_entries">
                                                <input type="hidden" name="record_id" value="<?= (int) $m['id'] ?>">
                                                <input type="hidden" name="return_tab" value="missing">
                                                <input type="date" name="corrected_date" class="form-control form-control-sm" required value="2023-12-01" style="max-width: 150px;">
                                                <button type="submit" class="btn btn-sm btn-success fw-bold py-1 px-2 text-nowrap">
                                                    <i class="bi bi-check-lg me-1"></i>Set Date
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tab 3: Projected Rows -->
    <?php if ($activeTab === 'projected'): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title fw-bold text-dark mb-0">Pre-filled Projected Calendar Rows (<code>is_projected = 1</code>)</h5>
                <small class="text-muted">Calendar dates pre-filled past the import cut-off (3-Nov-2026). These rows are excluded from historical totals.</small>
            </div>
            <div class="card-body p-0">
                <?php if (empty($projectedRows)): ?>
                    <div class="state-empty py-5 text-center">
                        <i class="bi bi-calendar-check display-4 text-muted mb-3 d-block"></i>
                        <h5 class="fw-semibold">No Projected Rows Flagged</h5>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Batch Code</th>
                                    <th>Sheet</th>
                                    <th>Excel Row</th>
                                    <th>Projected Date</th>
                                    <th>Opening Count</th>
                                    <th>Deaths</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projectedRows as $p): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold text-dark"><?= e($p['batch_code']) ?></td>
                                        <td><?= e($p['legacy_sheet']) ?></td>
                                        <td><span class="badge bg-light text-dark border">Row <?= (int) $p['excel_row'] ?></span></td>
                                        <td class="text-primary fw-semibold"><?= e($p['record_date']) ?></td>
                                        <td><?= (int) $p['opening_count'] ?></td>
                                        <td><?= (int) $p['deaths'] ?></td>
                                        <td><?= (int) $p['balance'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tab 4: Skipped Summary -->
    <?php if ($activeTab === 'skipped'): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title fw-bold text-dark mb-0">Import Log: Skipped Label & Formula Rows</h5>
                <small class="text-muted">Rows skipped during import (month-end totals, non-data labels, stray keystrokes) with logged reasons</small>
            </div>
            <div class="card-body p-0">
                <?php if (empty($skippedSummary)): ?>
                    <div class="state-empty py-5 text-center">
                        <i class="bi bi-journal-check display-4 text-muted mb-3 d-block"></i>
                        <h5 class="fw-semibold">No Skipped Rows Logged</h5>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Source File</th>
                                    <th>Total CSV Rows</th>
                                    <th>Imported</th>
                                    <th>Skipped</th>
                                    <th>Skipped Detail / Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($skippedSummary as $s): ?>
                                    <?php 
                                    $detail = !empty($s['skipped_detail']) ? json_decode((string)$s['skipped_detail'], true) : [];
                                    $preview = is_array($detail) && !empty($detail[0]['reason']) ? $detail[0]['reason'] : 'Label / Month-End formula row';
                                    ?>
                                    <tr>
                                        <td class="ps-3 font-monospace fw-semibold text-dark"><?= e($s['source_file']) ?></td>
                                        <td><?= (int) $s['rows_in_csv'] ?></td>
                                        <td><span class="badge bg-success-subtle text-success border border-success-subtle"><?= (int) $s['rows_imported'] ?></span></td>
                                        <td><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><?= (int) $s['rows_skipped'] ?></span></td>
                                        <td>
                                            <small class="text-muted d-block"><?= e($preview) ?></small>
                                            <?php if (is_array($detail) && count($detail) > 1): ?>
                                                <small class="text-secondary">(and <?= count($detail) - 1 ?> more logged items)</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
