<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                    <i class="bi bi-shield-lock-fill me-1"></i>Security Audit
                </span>
                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                    Total Records: <?= number_format($totalRecords) ?>
                </span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-0">System Audit Trail Log</h1>
            <p class="text-muted small mb-0 mt-1">Immutable security activity log: authentication events, data mutations, financial modifications and admin actions.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/admin/audit/export') ?>" class="btn btn-outline-success btn-sm fw-bold rounded-pill px-3 shadow-sm">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export Audit CSV
            </a>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Total Events</span>
                    <div class="p-1.5 bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                        <?= icon('report', 16) ?>
                    </div>
                </div>
                <div class="fs-4 fw-bold text-primary mt-1"><?= number_format($totalRecords) ?></div>
                <small class="text-muted">Recorded audit logs</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Active Tables</span>
                    <div class="p-1.5 bg-success-subtle rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                        <?= icon('backup', 16) ?>
                    </div>
                </div>
                <div class="fs-4 fw-bold text-success mt-1"><?= count($tablesList) ?></div>
                <small class="text-muted">Tracked database tables</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">System Users</span>
                    <div class="p-1.5 bg-info-subtle text-info rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                        <i class="bi bi-people-fill" style="font-size: 0.85rem;"></i>
                    </div>
                </div>
                <div class="fs-4 fw-bold text-info mt-1"><?= count($usersList) ?></div>
                <small class="text-muted">Users with audit history</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Integrity</span>
                    <div class="p-1.5 bg-warning-subtle text-warning rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                        <i class="bi bi-shield-lock" style="font-size: 0.85rem;"></i>
                    </div>
                </div>
                <div class="fs-5 fw-bold text-success mt-1 d-flex align-items-center gap-1">
                    <i class="bi bi-check-circle-fill"></i> Tamper-Proof
                </div>
                <small class="text-muted">Trigger-enforced security</small>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
        <form method="GET" action="<?= url('/admin/audit') ?>" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.75rem;">Action Type</label>
                <select name="action" class="form-select form-select-sm rounded-3">
                    <option value="">All Actions</option>
                    <option value="login" <?= ($actionFilter ?? '') === 'login' ? 'selected' : '' ?>>Login</option>
                    <option value="logout" <?= ($actionFilter ?? '') === 'logout' ? 'selected' : '' ?>>Logout</option>
                    <option value="create" <?= ($actionFilter ?? '') === 'create' ? 'selected' : '' ?>>Create</option>
                    <option value="update" <?= ($actionFilter ?? '') === 'update' ? 'selected' : '' ?>>Update</option>
                    <option value="delete" <?= ($actionFilter ?? '') === 'delete' ? 'selected' : '' ?>>Delete</option>
                    <option value="import" <?= ($actionFilter ?? '') === 'import' ? 'selected' : '' ?>>Import</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.75rem;">Target Entity / Table</label>
                <select name="table" class="form-select form-select-sm rounded-3">
                    <option value="">All Tables</option>
                    <?php foreach ($tablesList as $tbl): ?>
                        <option value="<?= e($tbl) ?>" <?= ($tableFilter ?? '') === $tbl ? 'selected' : '' ?>><?= e($tbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.75rem;">Acting User</label>
                <select name="user_id" class="form-select form-select-sm rounded-3">
                    <option value="">All Users</option>
                    <?php foreach ($usersList as $usr): ?>
                        <option value="<?= e($usr['id']) ?>" <?= ((string)($userFilter ?? '')) === (string)$usr['id'] ? 'selected' : '' ?>>
                            <?= e($usr['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.75rem;">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm rounded-3" value="<?= e($dateFrom ?? '') ?>">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-success btn-sm w-100 rounded-3 fw-bold">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="<?= url('/admin/audit') ?>" class="btn btn-outline-secondary btn-sm rounded-3 px-2.5" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Desktop Z-Fold Table (High Density, No Messy Cutoffs) -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4 d-none d-md-block">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                <thead class="table-light text-muted small text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                    <tr>
                        <th scope="col" class="ps-3 py-3" style="width: 220px;">Timestamp & Actor</th>
                        <th scope="col" class="py-3" style="width: 140px;">Action Type</th>
                        <th scope="col" class="py-3" style="width: 180px;">Target Entity</th>
                        <th scope="col" class="pe-3 py-3">Mutation Summary & Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-shield-check display-6 d-block mb-2 text-muted"></i>
                                No audit log records found matching filter criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $action = strtolower((string)$log['action']);
                            $badgeClass = match ($action) {
                                'create' => 'bg-success-subtle text-success border border-success-subtle',
                                'update' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                'delete' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                'login' => 'bg-info-subtle text-info border border-info-subtle',
                                'logout' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                'import' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                default => 'bg-light text-dark border'
                            };
                            $actionIcon = match ($action) {
                                'create' => 'bi-plus-circle',
                                'update' => 'bi-pencil',
                                'delete' => 'bi-trash',
                                'login' => 'bi-box-arrow-in-right',
                                'logout' => 'bi-box-arrow-right',
                                'import' => 'bi-cloud-arrow-down',
                                default => 'bi-activity'
                            };

                            // Parse changes for clean readable summary
                            $oldJson = $log['old_values'] ?? '';
                            $newJson = $log['new_values'] ?? '';
                            $summaryText = 'No field details';
                            if (!empty($newJson)) {
                                $decodedNew = json_decode((string)$newJson, true);
                                if (is_array($decodedNew)) {
                                    $keys = array_slice(array_keys($decodedNew), 0, 4);
                                    $summaryText = 'Modified: ' . implode(', ', $keys) . (count($decodedNew) > 4 ? '...' : '');
                                }
                            } elseif (!empty($oldJson)) {
                                $decodedOld = json_decode((string)$oldJson, true);
                                if (is_array($decodedOld)) {
                                    $keys = array_slice(array_keys($decodedOld), 0, 4);
                                    $summaryText = 'Previous: ' . implode(', ', $keys) . (count($decodedOld) > 4 ? '...' : '');
                                }
                            }
                            ?>
                            <tr style="transition: background-color 0.15s ease;">
                                <!-- Column 1: Timestamp & Actor (Z-Fold Left) -->
                                <td class="ps-3 py-2.5">
                                    <div class="fw-bold font-monospace small text-dark">
                                        <?= date('d-M-Y H:i:s', strtotime($log['created_at'])) ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-1.5 mt-0.5">
                                        <span class="badge bg-light text-dark border" style="font-size: 0.68rem;">
                                            <i class="bi bi-person me-1"></i><?= e($log['username'] ?? 'System') ?>
                                        </span>
                                        <?php if (!empty($log['role_name'])): ?>
                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.62rem;">
                                                <?= strtoupper(e($log['role_name'])) ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="text-muted font-monospace" style="font-size: 0.68rem;">
                                            <?= e($log['ip'] ?? '127.0.0.1') ?>
                                        </span>
                                    </div>
                                </td>

                                <!-- Column 2: Action Type Badge -->
                                <td class="py-2.5">
                                    <span class="badge <?= $badgeClass ?> fw-bold px-2 py-1 d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                        <i class="bi <?= $actionIcon ?>"></i>
                                        <?= strtoupper(e($log['action'])) ?>
                                    </span>
                                </td>

                                <!-- Column 3: Target Entity & Record -->
                                <td class="py-2.5">
                                    <div class="d-flex align-items-center gap-1.5">
                                        <span class="badge bg-light text-dark border font-monospace fw-bold" style="font-size: 0.75rem;">
                                            <?= e($log['table_name'] ?? 'system') ?>
                                        </span>
                                        <?php if (!empty($log['record_id'])): ?>
                                            <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 0.68rem;">
                                                #<?= e((string)$log['record_id']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Column 4: Summary & Inspect Button (Z-Fold Right) -->
                                <td class="pe-3 py-2.5">
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <span class="text-muted font-monospace small text-truncate" style="max-width: 320px;" title="<?= e($summaryText) ?>">
                                            <?= e($summaryText) ?>
                                        </span>
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 py-0.5 fw-semibold js-audit-view-btn"
                                                style="font-size: 0.75rem;"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#auditDiffModal"
                                                data-id="<?= e((string)$log['id']) ?>"
                                                data-timestamp="<?= date('d-M-Y H:i:s', strtotime($log['created_at'])) ?>"
                                                data-user="<?= e($log['username'] ?? 'System') ?> (<?= strtoupper(e($log['role_name'] ?? 'USER')) ?>)"
                                                data-ip="<?= e($log['ip'] ?? '127.0.0.1') ?>"
                                                data-action="<?= strtoupper(e($log['action'])) ?>"
                                                data-entity="<?= e($log['table_name'] ?? '—') ?> #<?= e((string)($log['record_id'] ?? '—')) ?>"
                                                data-old="<?= htmlspecialchars((string)($log['old_values'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-new="<?= htmlspecialchars((string)($log['new_values'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="bi bi-eye me-1"></i>View Diff
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Stacked Card View (< 768px - Z-Fold Method) -->
    <div class="d-flex flex-column gap-2.5 d-md-none mb-4">
        <?php if (empty($logs)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center text-muted bg-white">
                No audit records found.
            </div>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
                <?php
                $action = strtolower((string)$log['action']);
                $badgeClass = match ($action) {
                    'create' => 'bg-success-subtle text-success border border-success-subtle',
                    'update' => 'bg-primary-subtle text-primary border border-primary-subtle',
                    'delete' => 'bg-danger-subtle text-danger border border-danger-subtle',
                    'login' => 'bg-info-subtle text-info border border-info-subtle',
                    'logout' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                    'import' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                    default => 'bg-light text-dark border'
                };
                ?>
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <!-- Top Z-Line: Actor + Time (Left) & Action Badge (Right) -->
                    <div class="d-flex justify-content-between align-items-center mb-1.5">
                        <div class="d-flex align-items-center gap-1.5">
                            <span class="badge bg-light text-dark border fw-bold">@<?= e($log['username'] ?? 'System') ?></span>
                            <span class="text-muted font-monospace small" style="font-size: 0.72rem;">
                                <?= date('d-M H:i', strtotime($log['created_at'])) ?>
                            </span>
                        </div>
                        <span class="badge <?= $badgeClass ?> fw-bold" style="font-size: 0.68rem;">
                            <?= strtoupper(e($log['action'])) ?>
                        </span>
                    </div>

                    <!-- Middle: Entity & Record # -->
                    <div class="d-flex align-items-center gap-1.5 mb-2 bg-light p-1.5 rounded-3">
                        <span class="badge bg-white text-dark border font-monospace small">
                            <?= e($log['table_name'] ?? 'system') ?>
                        </span>
                        <span class="small font-monospace text-muted">#<?= e((string)($log['record_id'] ?? '—')) ?></span>
                        <span class="text-muted ms-auto font-monospace" style="font-size: 0.68rem;"><?= e($log['ip'] ?? '127.0.0.1') ?></span>
                    </div>

                    <!-- Bottom Z-Line: Button to view details -->
                    <div class="d-flex justify-content-end pt-1 border-top">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 fw-semibold js-audit-view-btn"
                                style="font-size: 0.75rem;"
                                data-bs-toggle="modal" 
                                data-bs-target="#auditDiffModal"
                                data-id="<?= e((string)$log['id']) ?>"
                                data-timestamp="<?= date('d-M-Y H:i:s', strtotime($log['created_at'])) ?>"
                                data-user="<?= e($log['username'] ?? 'System') ?> (<?= strtoupper(e($log['role_name'] ?? 'USER')) ?>)"
                                data-ip="<?= e($log['ip'] ?? '127.0.0.1') ?>"
                                data-action="<?= strtoupper(e($log['action'])) ?>"
                                data-entity="<?= e($log['table_name'] ?? '—') ?> #<?= e((string)($log['record_id'] ?? '—')) ?>"
                                data-old="<?= htmlspecialchars((string)($log['old_values'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-new="<?= htmlspecialchars((string)($log['new_values'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-eye me-1"></i>Inspect Audit Details
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-center">
            <nav aria-label="Audit log navigation">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link rounded-start-pill" href="<?= url('/admin/audit?page=' . ($page - 1) . '&action=' . urlencode($actionFilter) . '&table=' . urlencode($tableFilter) . '&user_id=' . urlencode($userFilter) . '&date_from=' . urlencode($dateFrom)) ?>">Previous</a>
                    </li>
                    <li class="page-item active">
                        <span class="page-link"><?= $page ?> / <?= $totalPages ?></span>
                    </li>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link rounded-end-pill" href="<?= url('/admin/audit?page=' . ($page + 1) . '&action=' . urlencode($actionFilter) . '&table=' . urlencode($tableFilter) . '&user_id=' . urlencode($userFilter) . '&date_from=' . urlencode($dateFrom)) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Audit Details & Value Diff Inspector -->
<div class="modal fade" id="auditDiffModal" tabindex="-1" aria-labelledby="auditDiffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="p-1.5 bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-shield-check"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0 fs-6" id="auditDiffModalLabel">Audit Event Details</h5>
                        <small class="text-muted" id="auditModalSubtitle">Record inspection</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Metadata Grid -->
                <div class="row g-2 mb-3 bg-light p-3 rounded-3">
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">ACTION</small>
                        <span class="fw-bold" id="auditModalAction">—</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">ENTITY & ID</small>
                        <span class="fw-bold font-monospace" id="auditModalEntity">—</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">ACTOR & ROLE</small>
                        <span class="fw-semibold small" id="auditModalUser">—</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">IP & TIME</small>
                        <span class="text-muted font-monospace small" id="auditModalTime">—</span>
                    </div>
                </div>

                <!-- Values Diff Viewer -->
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="card border rounded-3 p-3 bg-light h-100">
                            <strong class="d-block text-secondary small mb-2">
                                <i class="bi bi-arrow-left-circle me-1"></i>Previous Values (Before)
                            </strong>
                            <pre class="bg-white p-2.5 rounded border small font-monospace mb-0 overflow-auto" style="max-height: 260px; font-size: 0.75rem;" id="auditModalOldPre">None</pre>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="card border rounded-3 p-3 bg-light h-100">
                            <strong class="d-block text-success small mb-2">
                                <i class="bi bi-arrow-right-circle me-1"></i>New / Current Values (After)
                            </strong>
                            <pre class="bg-white p-2.5 rounded border small font-monospace mb-0 overflow-auto" style="max-height: 260px; font-size: 0.75rem;" id="auditModalNewPre">None</pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-4">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const viewButtons = document.querySelectorAll('.js-audit-view-btn');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const action = this.dataset.action;
            const entity = this.dataset.entity;
            const user = this.dataset.user;
            const time = this.dataset.timestamp;
            const ip = this.dataset.ip;
            const oldVal = this.dataset.old;
            const newVal = this.dataset.new;

            document.getElementById('auditModalSubtitle').textContent = `Audit Log #${this.dataset.id} • Recorded at ${time}`;
            document.getElementById('auditModalAction').textContent = action;
            document.getElementById('auditModalEntity').textContent = entity;
            document.getElementById('auditModalUser').textContent = user;
            document.getElementById('auditModalTime').textContent = `${ip} • ${time}`;

            // Format JSON nicely
            try {
                document.getElementById('auditModalOldPre').textContent = oldVal ? JSON.stringify(JSON.parse(oldVal), null, 2) : '— No previous values —';
            } catch (e) {
                document.getElementById('auditModalOldPre').textContent = oldVal || '— No previous values —';
            }

            try {
                document.getElementById('auditModalNewPre').textContent = newVal ? JSON.stringify(JSON.parse(newVal), null, 2) : '— No new values —';
            } catch (e) {
                document.getElementById('auditModalNewPre').textContent = newVal || '— No new values —';
            }
        });
    });
});
</script>
