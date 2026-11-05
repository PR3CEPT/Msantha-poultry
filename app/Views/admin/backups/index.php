<?php declare(strict_types=1); ?>
<div class="p-3 p-md-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 fw-bold text-dark mb-0">Database Backups</h1>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Owner Only</span>
            </div>
            <p class="text-muted small mb-0">Automated nightly backups, 14-day retention, and emergency download snapshots</p>
        </div>
        <form method="POST" action="<?= url('/admin/backups/create') ?>" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-cloud-arrow-down-fill"></i>
                <span>Create Backup Now</span>
            </button>
        </form>
    </div>

    <!-- Backup Info Card -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm p-3">
                <div class="stat-label">Retention Policy</div>
                <div class="stat-value text-success">14 Days</div>
                <small class="text-muted">Older files auto-purged nightly</small>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm p-3">
                <div class="stat-label">Total Backups on Disk</div>
                <div class="stat-value text-primary"><?= count($backups) ?></div>
                <small class="text-muted">In <code>storage/backups/</code></small>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm p-3">
                <div class="stat-label">Nightly Cron Schedule</div>
                <div class="stat-value text-dark">02:00 AM</div>
                <small class="text-muted">Runs <code>cron/backup.php</code></small>
            </div>
        </div>
    </div>

    <!-- Backups Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="card-title fw-bold text-dark mb-0">Available Snapshot Files</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($backups)): ?>
                <div class="state-empty py-5 text-center">
                    <i class="bi bi-database-check display-4 text-muted mb-3 d-block"></i>
                    <h5 class="fw-semibold">No Backups Found</h5>
                    <p class="text-muted small mb-3">Click "Create Backup Now" to generate an immediate snapshot of the database.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Filename</th>
                                <th>Created At</th>
                                <th>File Size</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $b): ?>
                                <tr>
                                    <td class="ps-3 font-monospace fw-semibold text-dark">
                                        <i class="bi bi-file-earmark-code text-primary me-2"></i><?= e($b['filename']) ?>
                                    </td>
                                    <td><?= e($b['created_at']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($b['size_formatted']) ?></span></td>
                                    <td class="text-end pe-3">
                                        <a href="<?= url('/admin/backups/' . urlencode($b['filename']) . '/download') ?>" class="btn btn-sm btn-outline-success fw-semibold">
                                            <i class="bi bi-download me-1"></i>Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

