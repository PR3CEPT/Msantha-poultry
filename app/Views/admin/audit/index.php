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

    <!-- Audit Trail Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark fs-6"><i class="bi bi-shield-check text-success me-1"></i>Audit Records</span>
            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                Showing <?= count($logs) ?> of <?= number_format($totalRecords) ?>
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 155px;">Timestamp</th>
                        <th>User</th>
                        <th class="text-center">Action</th>
                        <th>Target Entity</th>
                        <th>IP Address</th>
                        <th>Change Summary</th>
                        <th class="text-end pe-3" style="width: 90px;">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-shield-check display-6 d-block mb-2 text-muted"></i>
                                <span class="fw-bold text-dark d-block">No Audit Events Found</span>
                                <span class="small">No records matched your selected filter criteria.</span>
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
                                'create' => 'bi-plus-circle-fill',
                                'update' => 'bi-pencil-fill',
                                'delete' => 'bi-trash-fill',
                                'login' => 'bi-box-arrow-in-right',
                                'logout' => 'bi-box-arrow-right',
                                'import' => 'bi-cloud-arrow-down-fill',
                                default => 'bi-activity'
                            };

                            // Decode changes for rich diff
                            $oldJson = $log['old_values'] ?? '';
                            $newJson = $log['new_values'] ?? '';
                            $oldData = !empty($oldJson) ? json_decode((string)$oldJson, true) : null;
                            $newData = !empty($newJson) ? json_decode((string)$newJson, true) : null;

                            $summaryText = '—';
                            if (is_array($newData) && !empty($newData)) {
                                $keys = array_slice(array_keys($newData), 0, 4);
                                $summaryText = 'Modified: ' . implode(', ', $keys) . (count($newData) > 4 ? '...' : '');
                            } elseif (is_array($oldData) && !empty($oldData)) {
                                $keys = array_slice(array_keys($oldData), 0, 4);
                                $summaryText = 'Previous: ' . implode(', ', $keys) . (count($oldData) > 4 ? '...' : '');
                            }
                            ?>
                            <tr>
                                <td class="ps-3 text-nowrap font-monospace small text-muted">
                                    <?= date('d-M-Y H:i:s', strtotime($log['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($log['username'] ?? 'System') ?></div>
                                    <?php if (!empty($log['role_name'])): ?>
                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">
                                            <?= strtoupper(e($log['role_name'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $badgeClass ?> fw-bold px-2 py-1 d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                        <i class="bi <?= $actionIcon ?>"></i>
                                        <?= strtoupper(e($log['action'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace fw-bold" style="font-size: 0.75rem;">
                                        <?= e($log['table_name'] ?? 'system') ?>
                                        <?php if (!empty($log['record_id'])): ?>
                                            #<?= e((string)$log['record_id']) ?>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="font-monospace small text-muted">
                                    <?= e($log['ip'] ?? '127.0.0.1') ?>
                                </td>
                                <td class="small text-muted font-monospace text-truncate" style="max-width: 240px;">
                                    <?= e($summaryText) ?>
                                </td>
                                <td class="text-end pe-3 text-nowrap">
                                    <?php if (!empty($oldJson) || !empty($newJson)): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-1" data-bs-toggle="collapse" data-bs-target="#auditRow<?= $log['id'] ?>" aria-expanded="false" style="font-size: 0.72rem;">
                                            <i class="bi bi-chevron-down me-1"></i>Diff
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if (!empty($oldJson) || !empty($newJson)): ?>
                                <tr class="collapse bg-light" id="auditRow<?= $log['id'] ?>">
                                    <td colspan="7" class="p-3">
                                        <div class="bg-white p-3 rounded-3 border">
                                            <div class="mb-2 d-flex justify-content-between align-items-center small text-muted">
                                                <span><strong>Event #<?= e((string)$log['id']) ?></strong> on <code><?= e($log['table_name'] ?? 'system') ?></code></span>
                                                <?php if (!empty($log['user_agent'])): ?>
                                                    <span class="font-monospace text-truncate" style="max-width: 400px;" title="<?= e($log['user_agent']) ?>"><?= e($log['user_agent']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($action === 'update' && is_array($newData)): ?>
                                                <div class="table-responsive">
                                                    <table class="table table-sm align-middle mb-0" style="font-size: 0.78rem;">
                                                        <thead class="table-light text-muted">
                                                            <tr>
                                                                <th>Field</th>
                                                                <th>Previous Value (Before)</th>
                                                                <th>Updated Value (After)</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($newData as $k => $newVal): ?>
                                                                <?php $prevVal = $oldData[$k] ?? '—'; ?>
                                                                <tr>
                                                                    <td class="font-monospace fw-semibold text-secondary"><?= e((string)$k) ?></td>
                                                                    <td class="font-monospace text-danger-emphasis">
                                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle text-wrap text-start">
                                                                            <?= is_array($prevVal) ? json_encode($prevVal) : e((string)$prevVal) ?>
                                                                        </span>
                                                                    </td>
                                                                    <td class="font-monospace text-success">
                                                                        <span class="badge bg-success-subtle text-success border border-success-subtle text-wrap text-start">
                                                                            <?= is_array($newVal) ? json_encode($newVal) : e((string)$newVal) ?>
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php else: ?>
                                                <div class="row g-2">
                                                    <?php if (!empty($oldJson)): ?>
                                                        <div class="col-md-6">
                                                            <small class="text-muted d-block fw-bold mb-1">Before:</small>
                                                            <pre class="bg-light p-2 rounded small font-monospace mb-0" style="max-height: 150px;"><?= e((string)$oldJson) ?></pre>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($newJson)): ?>
                                                        <div class="col-md-6">
                                                            <small class="text-success d-block fw-bold mb-1">After:</small>
                                                            <pre class="bg-light p-2 rounded small font-monospace mb-0 text-success" style="max-height: 150px;"><?= e((string)$newJson) ?></pre>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-center mt-4">
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

