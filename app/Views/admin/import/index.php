<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                    <i class="bi bi-cloud-arrow-down-fill me-1"></i>Data Management
                </span>
                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                    Ongoing CSV Engine
                </span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-0">Operational Data Import Center</h1>
            <p class="text-muted small mb-0 mt-1">Ingest ongoing operational batches via standard CSV uploads, or download pre-formatted data entry templates.</p>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-4 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                <div class="small fw-semibold"><?= e($flashSuccess) ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-4 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                <div class="small fw-semibold"><?= e($flashError) ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Downloadable CSV Templates Section -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <div>
                <h5 class="fw-bold text-dark mb-0 fs-6">
                    <i class="bi bi-file-earmark-spreadsheet-fill text-success me-2"></i>Downloadable CSV Templates for Data Entry
                </h5>
                <small class="text-muted">Use these standardized templates to prepare ongoing operational records for automated ingestion.</small>
            </div>
            <span class="badge bg-success-subtle text-success border border-success-subtle">5 Active Templates</span>
        </div>
        <div class="row g-3">
            <div class="col-12 col-sm-6 col-lg">
                <div class="p-3 bg-light rounded-4 border h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-1.5 mb-1">
                            <?= icon('egg', 20) ?>
                            <strong class="text-dark small">Egg Collections</strong>
                        </div>
                        <p class="text-muted small mb-3" style="font-size: 0.75rem;">Daily harvests, broken/consumed eggs, pen stock</p>
                    </div>
                    <a href="<?= url('/admin/import/template/eggs') ?>" class="btn btn-sm btn-outline-success rounded-pill w-100 fw-semibold" style="font-size: 0.75rem;">
                        <i class="bi bi-download me-1"></i>Template CSV
                    </a>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg">
                <div class="p-3 bg-light rounded-4 border h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-1.5 mb-1">
                            <?= icon('chicken', 20) ?>
                            <strong class="text-dark small">Flock Batches</strong>
                        </div>
                        <p class="text-muted small mb-3" style="font-size: 0.75rem;">Batch codes, breeds, start dates & starting counts</p>
                    </div>
                    <a href="<?= url('/admin/import/template/flocks') ?>" class="btn btn-sm btn-outline-success rounded-pill w-100 fw-semibold" style="font-size: 0.75rem;">
                        <i class="bi bi-download me-1"></i>Template CSV
                    </a>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg">
                <div class="p-3 bg-light rounded-4 border h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-1.5 mb-1">
                            <?= icon('vaccine', 20) ?>
                            <strong class="text-dark small">Flock Events</strong>
                        </div>
                        <p class="text-muted small mb-3" style="font-size: 0.75rem;">Daily mortality deaths, bird sales, culls & intake adds</p>
                    </div>
                    <a href="<?= url('/admin/import/template/events') ?>" class="btn btn-sm btn-outline-success rounded-pill w-100 fw-semibold" style="font-size: 0.75rem;">
                        <i class="bi bi-download me-1"></i>Template CSV
                    </a>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg">
                <div class="p-3 bg-light rounded-4 border h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-1.5 mb-1">
                            <?= icon('incubator', 20) ?>
                            <strong class="text-dark small">Incubations</strong>
                        </div>
                        <p class="text-muted small mb-3" style="font-size: 0.75rem;">Customer batches, intake dates, eggs set & rates</p>
                    </div>
                    <a href="<?= url('/admin/import/template/incubation') ?>" class="btn btn-sm btn-outline-success rounded-pill w-100 fw-semibold" style="font-size: 0.75rem;">
                        <i class="bi bi-download me-1"></i>Template CSV
                    </a>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg">
                <div class="p-3 bg-light rounded-4 border h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-1.5 mb-1">
                            <?= icon('cash', 20) ?>
                            <strong class="text-dark small">Cash Books</strong>
                        </div>
                        <p class="text-muted small mb-3" style="font-size: 0.75rem;">Financial receipts, farm expenses & business units</p>
                    </div>
                    <a href="<?= url('/admin/import/template/finance') ?>" class="btn btn-sm btn-outline-success rounded-pill w-100 fw-semibold" style="font-size: 0.75rem;">
                        <i class="bi bi-download me-1"></i>Template CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Active CSV Upload & Ingestion Engine -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
            <div class="p-2 bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="bi bi-upload"></i>
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-0 fs-6">Upload Ongoing Operational CSV</h5>
                <small class="text-muted">Select target module, choose dry-run validation or live ingestion, and upload your populated CSV file.</small>
            </div>
        </div>

        <form method="POST" action="<?= url('/admin/import/run') ?>" enctype="multipart/form-data" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Target Operational Module</label>
                <select name="module" class="form-select rounded-3" required>
                    <option value="eggs">Egg Collections (Daily harvest & trays)</option>
                    <option value="flocks">Flock Batches (Intakes & starting stock)</option>
                    <option value="events">Flock Events (Daily mortality & offloads)</option>
                    <option value="incubation">Incubation Jobs (Customer batches)</option>
                    <option value="finance">Multi-Unit Cash Books (Receipts & expenses)</option>
                </select>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Ingestion Mode</label>
                <select name="dry_run" class="form-select rounded-3">
                    <option value="1">Dry Run Preview (Validate without writing)</option>
                    <option value="0">Live Ingest (Commit records to database)</option>
                </select>
            </div>

            <div class="col-12 col-md-5">
                <label class="form-label small fw-semibold text-muted mb-1">Select CSV File</label>
                <input type="file" name="csv_file" class="form-control rounded-3" accept=".csv" required>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                <button type="submit" class="btn btn-success fw-bold px-4 py-2 rounded-pill shadow-sm">
                    <i class="bi bi-cloud-arrow-up-fill me-1"></i>Process Uploaded File
                </button>
            </div>
        </form>
    </div>

    <!-- Recent Import History Log -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-secondary"></i>
                <h6 class="fw-bold text-dark mb-0 fs-6">Recent Import History</h6>
            </div>
            <span class="badge bg-light text-muted border">System Ingestion Ledger</span>
        </div>
        <div class="table-responsive">
            <table class="table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3 py-3" style="width: 170px;">Timestamp</th>
                        <th class="py-3">Source File / Module</th>
                        <th class="text-end py-3" style="width: 130px;">Rows Imported</th>
                        <th class="text-end py-3" style="width: 130px;">Rows Skipped</th>
                        <th class="text-end pe-3 py-3" style="width: 120px;">Execution Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                No previous import logs recorded.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td class="ps-3 py-2.5 font-monospace small text-dark fw-semibold">
                                    <?= date('d-M-Y H:i', strtotime($h['imported_at'] ?? $h['created_at'] ?? 'now')) ?>
                                </td>
                                <td class="py-2.5 font-monospace small text-dark">
                                    <i class="bi bi-filetype-csv text-success me-1"></i><?= e($h['source_file'] ?? 'CSV Upload') ?>
                                </td>
                                <td class="text-end py-2.5 fw-bold text-success font-monospace">
                                    +<?= number_format((int)($h['rows_imported'] ?? 0)) ?>
                                </td>
                                <td class="text-end py-2.5 text-muted font-monospace">
                                    <?= number_format((int)($h['rows_skipped'] ?? 0)) ?>
                                </td>
                                <td class="text-end pe-3 py-2.5">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold" style="font-size: 0.7rem;">
                                        COMPLETED
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
