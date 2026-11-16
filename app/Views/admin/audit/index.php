<?php declare(strict_types=1); ?>

<style>
/* Audit Trail Executive Theme */
.zfold-container {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}

.zfold-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    overflow: hidden;
}

.zfold-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
}

.zfold-card.is-unfolded {
    border-color: #1b6d39;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
}

.zfold-header {
    padding: 0.9rem 1.15rem;
    cursor: pointer;
    user-select: none;
    background-color: #ffffff;
    transition: background-color 0.15s ease;
}

.zfold-header:hover {
    background-color: #f8fafc;
}

.zfold-tier-top,
.zfold-tier-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.zfold-toggle-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-size: 0.75rem;
    transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.2s ease, color 0.2s ease;
    cursor: pointer;
    padding: 0;
}

.zfold-card.is-unfolded .zfold-toggle-btn {
    transform: rotate(180deg);
    background: #1b6d39;
    color: #ffffff;
    border-color: #1b6d39;
}

.zfold-body {
    display: none;
    padding: 1rem 1.15rem;
    background-color: #fcfdfe;
    border-top: 1px dashed #e2e8f0;
    animation: zfoldSlideDown 0.22s ease forwards;
}

.zfold-card.is-unfolded .zfold-body {
    display: block;
}

@keyframes zfoldSlideDown {
    from {
        opacity: 0;
        transform: translateY(-6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

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

    <!-- Z-Fold Activity Log Container -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-bold text-dark fs-6">Audit Trail Records</span>
            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                Showing <?= count($logs) ?> of <?= number_format($totalRecords) ?>
            </span>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold shadow-sm" data-zfold-toggle-all="#auditZfoldContainer">
            <i class="bi bi-chevron-bar-expand me-1"></i>Unfold All
        </button>
    </div>

    <div class="zfold-container" id="auditZfoldContainer">
        <?php if (empty($logs)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted bg-white">
                <i class="bi bi-shield-check display-6 d-block mb-2 text-muted"></i>
                <h6 class="fw-bold text-dark">No Audit Events Found</h6>
                <p class="small text-muted mb-0">No records matched your selected filter criteria. Try adjusting dates or action types.</p>
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

                $summaryText = 'No field details';
                if (is_array($newData) && !empty($newData)) {
                    $keys = array_slice(array_keys($newData), 0, 4);
                    $summaryText = 'Modified: ' . implode(', ', $keys) . (count($newData) > 4 ? '...' : '');
                } elseif (is_array($oldData) && !empty($oldData)) {
                    $keys = array_slice(array_keys($oldData), 0, 4);
                    $summaryText = 'Previous: ' . implode(', ', $keys) . (count($oldData) > 4 ? '...' : '');
                }
                $cardBorderClass = match ($action) {
                    'create' => 'border-start border-4 border-success',
                    'update' => 'border-start border-4 border-primary',
                    'delete' => 'border-start border-4 border-danger',
                    'login' => 'border-start border-4 border-info',
                    'import' => 'border-start border-4 border-warning',
                    default => 'border-start border-4 border-secondary'
                };
                ?>
                <div class="zfold-card mb-2.5 <?= $cardBorderClass ?>">
                    <!-- Z-Fold Header: 2-Tier Folding Scan -->
                    <div class="zfold-header" onclick="if (!event.target.closest('a, button, input, select, .no-fold')) this.closest('.zfold-card').classList.toggle('is-unfolded');">
                        <!-- Tier 1: Timestamp & Actor on Left <---> Action Badge & Fold Toggle on Right -->
                        <div class="zfold-tier-top">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="fw-bold font-monospace text-dark small" style="font-size: 0.82rem;">
                                    <i class="bi bi-clock me-1 text-muted"></i><?= date('d-M-Y H:i:s', strtotime($log['created_at'])) ?>
                                </span>
                                <span class="badge bg-light text-dark border" style="font-size: 0.72rem;">
                                    <i class="bi bi-person me-1"></i><?= e($log['username'] ?? 'System') ?>
                                </span>
                                <?php if (!empty($log['role_name'])): ?>
                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">
                                        <?= strtoupper(e($log['role_name'])) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="text-muted font-monospace" style="font-size: 0.7rem;">
                                    <?= e($log['ip'] ?? '127.0.0.1') ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge <?= $badgeClass ?> fw-bold px-2 py-1 d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                    <i class="bi <?= $actionIcon ?>"></i>
                                    <?= strtoupper(e($log['action'])) ?>
                                </span>
                                <button type="button" class="zfold-toggle-btn" aria-label="Toggle details" onclick="event.stopPropagation(); this.closest('.zfold-card').classList.toggle('is-unfolded');">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Tier 2: Target Entity on Left <---> Summary & ID on Right -->
                        <div class="zfold-tier-bottom mt-1">
                            <div class="d-flex align-items-center gap-2 text-truncate" style="max-width: 72%;">
                                <span class="badge bg-light text-dark border font-monospace fw-bold" style="font-size: 0.75rem;">
                                    <?= e($log['table_name'] ?? 'system') ?>
                                    <?php if (!empty($log['record_id'])): ?>
                                        #<?= e((string)$log['record_id']) ?>
                                    <?php endif; ?>
                                </span>
                                <span class="text-muted small text-truncate font-monospace" style="font-size: 0.78rem;">
                                    <?= e($summaryText) ?>
                                </span>
                            </div>
                            <span class="text-muted font-monospace" style="font-size: 0.72rem;">
                                Event #<?= e((string)$log['id']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Z-Fold Body: Inline Diff Viewer & Audit Inspection -->
                    <div class="zfold-body">
                        <div class="row g-3">
                            <!-- Context Badges -->
                            <div class="col-12">
                                <div class="p-2.5 bg-white rounded-3 border d-flex flex-wrap gap-3 align-items-center text-muted small font-monospace" style="font-size: 0.75rem;">
                                    <div><strong class="text-dark">User:</strong> <?= e($log['username'] ?? 'System') ?> (<?= strtoupper(e($log['role_name'] ?? 'USER')) ?>)</div>
                                    <div><strong class="text-dark">IP Address:</strong> <?= e($log['ip'] ?? '127.0.0.1') ?></div>
                                    <div><strong class="text-dark">Target:</strong> <?= e($log['table_name'] ?? 'system') ?> <?= !empty($log['record_id']) ? '#' . e((string)$log['record_id']) : '' ?></div>
                                    <div><strong class="text-dark">Timestamp:</strong> <?= date('d-M-Y H:i:s', strtotime($log['created_at'])) ?></div>
                                    <?php if (!empty($log['user_agent'])): ?>
                                        <div class="text-truncate" style="max-width: 320px;" title="<?= e($log['user_agent']) ?>"><strong class="text-dark">Agent:</strong> <?= e($log['user_agent']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Field-by-field Comparison / Diff -->
                            <?php if ($action === 'update' && is_array($newData)): ?>
                                <div class="col-12">
                                    <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-arrow-left-right text-primary me-1"></i>Field Modifications:</h6>
                                    <div class="table-responsive bg-white rounded-3 border">
                                        <table class="table table-sm align-middle mb-0" style="font-size: 0.8rem;">
                                            <thead class="table-light text-muted small">
                                                <tr>
                                                    <th class="ps-3 py-2" style="width: 220px;">Modified Field</th>
                                                    <th class="py-2" style="width: 40%;">Previous Value (Before)</th>
                                                    <th class="pe-3 py-2" style="width: 40%;">Updated Value (After)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($newData as $k => $newVal): ?>
                                                    <?php $prevVal = $oldData[$k] ?? '—'; ?>
                                                    <tr>
                                                        <td class="ps-3 py-1.5 font-monospace fw-semibold text-secondary"><?= e((string)$k) ?></td>
                                                        <td class="py-1.5 font-monospace text-danger-emphasis">
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle text-wrap text-start">
                                                                <?= is_array($prevVal) ? json_encode($prevVal) : e((string)$prevVal) ?>
                                                            </span>
                                                        </td>
                                                        <td class="pe-3 py-1.5 font-monospace text-success">
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle text-wrap text-start">
                                                                <?= is_array($newVal) ? json_encode($newVal) : e((string)$newVal) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php elseif ($action === 'create' && is_array($newData)): ?>
                                <div class="col-12">
                                    <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-plus-circle text-success me-1"></i>Created Record Payload:</h6>
                                    <div class="p-3 bg-white rounded-3 border">
                                        <div class="row g-2">
                                            <?php foreach ($newData as $k => $val): ?>
                                                <div class="col-6 col-md-4 col-lg-3">
                                                    <small class="text-muted d-block font-monospace" style="font-size: 0.7rem;"><?= strtoupper(e((string)$k)) ?></small>
                                                    <span class="font-monospace small fw-semibold text-dark"><?= is_array($val) ? json_encode($val) : e((string)$val) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($action === 'delete' && is_array($oldData)): ?>
                                <div class="col-12">
                                    <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-trash text-danger me-1"></i>Deleted Record Payload:</h6>
                                    <div class="p-3 bg-white rounded-3 border">
                                        <div class="row g-2">
                                            <?php foreach ($oldData as $k => $val): ?>
                                                <div class="col-6 col-md-4 col-lg-3">
                                                    <small class="text-muted d-block font-monospace" style="font-size: 0.7rem;"><?= strtoupper(e((string)$k)) ?></small>
                                                    <span class="font-monospace small text-danger"><?= is_array($val) ? json_encode($val) : e((string)$val) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="col-12 col-md-6">
                                    <div class="p-2.5 bg-white rounded-3 border">
                                        <small class="text-muted d-block fw-bold mb-1">Previous Values</small>
                                        <pre class="bg-light p-2 rounded small font-monospace mb-0 text-muted" style="max-height: 160px; font-size: 0.72rem;"><?= !empty($oldJson) ? e((string)$oldJson) : '— No previous values —' ?></pre>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="p-2.5 bg-white rounded-3 border">
                                        <small class="text-success d-block fw-bold mb-1">New Values</small>
                                        <pre class="bg-light p-2 rounded small font-monospace mb-0 text-dark" style="max-height: 160px; font-size: 0.72rem;"><?= !empty($newJson) ? e((string)$newJson) : '— No new values —' ?></pre>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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

<script>
// Immediate Resilient Z-Fold Click Controller
(function() {
    document.addEventListener('click', function(e) {
        if (e._zfoldHandled) return;
        var header = e.target.closest('.zfold-header');
        if (header) {
            if (e.target.closest('a') || e.target.closest('button:not(.zfold-toggle-btn)') || e.target.closest('.no-fold')) return;
            e._zfoldHandled = true;
            var card = header.closest('.zfold-card');
            if (card) {
                card.classList.toggle('is-unfolded');
            }
        }
        var toggleBtn = e.target.closest('[data-zfold-toggle-all]');
        if (toggleBtn) {
            e._zfoldHandled = true;
            var sel = toggleBtn.getAttribute('data-zfold-toggle-all') || '.zfold-container';
            var container = document.querySelector(sel);
            if (container) {
                var cards = container.querySelectorAll('.zfold-card');
                var isExpanded = toggleBtn.getAttribute('data-state') === 'expanded';
                cards.forEach(function(c) {
                    if (isExpanded) c.classList.remove('is-unfolded');
                    else c.classList.add('is-unfolded');
                });
                toggleBtn.setAttribute('data-state', isExpanded ? 'collapsed' : 'expanded');
                toggleBtn.innerHTML = isExpanded 
                    ? '<i class="bi bi-chevron-bar-expand me-1"></i>Unfold All'
                    : '<i class="bi bi-chevron-bar-contract me-1"></i>Fold All';
            }
        }
    });
})();
</script>
