<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Flash Messages -->
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= e($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 fw-bold mb-0 text-dark">SMS Notification Centre</h1>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
                    Outbox & Dispatch Log
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Automated reminders, incubation alerts, debt collection, vaccine notices, and bulk SMS broadcasting.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="<?= url('/admin/sms-templates') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-file-text me-1"></i>Edit Templates
            </a>
            <?php if (\App\Core\Auth::hasRole('owner')): ?>
                <a href="<?= url('/admin/settings/sms') ?>" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-gear me-1"></i>Gateway Settings
                </a>
            <?php endif; ?>
            <?php if (\App\Core\Auth::can('sms', 'create')): ?>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkSmsModal">
                    <i class="bi bi-broadcast me-1"></i>Send Bulk SMS
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-warning">
                <span class="text-muted small fw-semibold text-uppercase">Queued / Sending</span>
                <div class="fs-4 fw-bold text-warning mt-1 font-monospace"><?= number_format($stats['queued']) ?></div>
                <small class="text-muted">Awaiting cron dispatch</small>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                <span class="text-muted small fw-semibold text-uppercase">Delivered / Sent</span>
                <div class="fs-4 fw-bold text-success mt-1 font-monospace"><?= number_format($stats['sent']) ?></div>
                <small class="text-muted">Successfully transmitted</small>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-danger">
                <span class="text-muted small fw-semibold text-uppercase">Failed Deliveries</span>
                <div class="fs-4 fw-bold text-danger mt-1 font-monospace"><?= number_format($stats['failed']) ?></div>
                <small class="text-muted">Retried 3x or invalid number</small>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
        <div class="card-header bg-white border-bottom p-3">
            <form method="GET" action="<?= url('/admin/sms') ?>" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="<?= e($search ?? '') ?>" class="form-control" placeholder="Search phone number or customer name...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="all" <?= ($status ?? 'all') === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="queued" <?= ($status ?? '') === 'queued' ? 'selected' : '' ?>>Queued Only</option>
                        <option value="sent" <?= ($status ?? '') === 'sent' ? 'selected' : '' ?>>Sent Only</option>
                        <option value="delivered" <?= ($status ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered Only</option>
                        <option value="failed" <?= ($status ?? '') === 'failed' ? 'selected' : '' ?>>Failed Only</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-filter me-1"></i>Filter</button>
                    <?php if (!empty($search) || ($status ?? 'all') !== 'all'): ?>
                        <a href="<?= url('/admin/sms') ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0 fs-6"><i class="bi bi-chat-dots me-2 text-primary"></i>SMS Dispatch Log & Outbox</h5>
            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                Showing <?= count($outbox) ?> of <?= number_format($totalRows) ?> Messages
            </span>
        </div>

        <!-- Outbox Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase font-monospace" style="font-size: 0.72rem;">
                    <tr>
                        <th class="ps-3 py-3">ID / Time</th>
                        <th class="py-3">Recipient</th>
                        <th class="py-3">Template / Type</th>
                        <th class="py-3">Message Body</th>
                        <th class="text-center py-3">Status</th>
                        <th class="text-center py-3">Attempts</th>
                        <th class="text-end pe-3 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($outbox)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">No SMS messages in outbox.</td></tr>
                    <?php else: ?>
                        <?php foreach ($outbox as $item): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark font-monospace">#<?= $item['id'] ?></div>
                                    <small class="text-muted font-monospace"><?= date('d-M H:i', strtotime($item['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-primary font-monospace"><?= e($item['to_phone']) ?></div>
                                    <?php if (!empty($item['customer_name'])): ?>
                                        <small class="text-muted"><?= e($item['customer_name']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= e($item['template_code'] ?: 'DIRECT') ?></span>
                                </td>
                                <td>
                                    <div class="text-dark small" style="max-width: 320px;"><?= e($item['body']) ?></div>
                                    <?php if (!empty($item['error'])): ?>
                                        <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i><?= e($item['error']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $badge = match ($item['status']) {
                                        'sent' => 'bg-info text-white',
                                        'delivered' => 'bg-success text-white',
                                        'failed' => 'bg-danger text-white',
                                        default => 'bg-warning text-dark',
                                    };
                                    ?>
                                    <span class="badge <?= $badge ?> text-uppercase"><?= e($item['status']) ?></span>
                                </td>
                                <td class="text-center font-monospace">
                                    <span class="badge bg-light text-secondary border"><?= $item['attempts'] ?> / 3</span>
                                </td>
                                <td class="text-end pe-3">
                                    <?php if ($item['status'] === 'failed' && \App\Core\Auth::can('sms', 'update')): ?>
                                        <form method="POST" action="<?= url('/admin/sms/retry') ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="btn btn-outline-primary btn-sm py-0 px-2" title="Retry sending">
                                                <i class="bi bi-arrow-repeat me-1"></i>Retry
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
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
                <span class="text-muted small">Showing page <?= $page ?> of <?= $totalPages ?> (<?= number_format($totalRows) ?> total SMS)</span>
                <nav aria-label="SMS outbox navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('/admin/sms?page=' . ($page - 1) . '&status=' . urlencode($status) . '&q=' . urlencode($search)) ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        <li class="page-item active">
                            <span class="page-link"><?= $page ?></span>
                        </li>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('/admin/sms?page=' . ($page + 1) . '&status=' . urlencode($status) . '&q=' . urlencode($search)) ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Bulk SMS -->
<div class="modal fade" id="bulkSmsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?= url('/admin/sms/bulk') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-broadcast me-2 text-primary"></i>Broadcast Bulk SMS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">
                    Send notices, promotion announcements, or reminders to multiple opted-in customers. 
                    (Internal farm accounts and opted-out customers are automatically excluded).
                </p>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Select Customer Recipients</label>
                    <div class="border rounded p-2 bg-light" style="max-height: 180px; overflow-y: auto;">
                        <div class="form-check border-bottom pb-1 mb-2">
                            <input class="form-check-input" type="checkbox" id="selectAllCust" onclick="toggleSelectAll(this)">
                            <label class="form-check-label fw-bold" for="selectAllCust">Select All Opted-In Customers (<?= count($customers) ?>)</label>
                        </div>
                        <?php foreach ($customers as $c): ?>
                            <div class="form-check">
                                <input class="form-check-input cust-checkbox" type="checkbox" name="customer_ids[]" value="<?= $c['id'] ?>" id="c_<?= $c['id'] ?>" onchange="updateEstimator()">
                                <label class="form-check-label small" for="c_<?= $c['id'] ?>">
                                    <?= e($c['name']) ?> <span class="text-muted">(<?= e($c['phone']) ?>)</span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Message Text</label>
                    <textarea name="message" id="bulkMessageText" class="form-control" rows="4" placeholder="Type broadcast message. You can use placeholder {customer_name}." required oninput="updateEstimator()"></textarea>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <small class="text-muted">Available placeholder: <code>{customer_name}</code></small>
                        <small id="charCounter" class="fw-semibold text-secondary">0 chars | 1 segment</small>
                    </div>
                </div>

                <!-- Cost Estimator Card -->
                <div class="card border border-primary-subtle bg-primary-subtle rounded-3 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase fw-bold text-primary small d-block">Cost Estimator</span>
                            <span id="estimateRecipients" class="text-dark fw-bold">0 recipients</span> &times; 
                            <span id="estimateSegments" class="text-dark fw-bold">1 segment</span> @ 
                            <span class="text-dark fw-semibold"><?= $costPerSegment ?> MWK/SMS</span>
                        </div>
                        <div class="text-end">
                            <span class="text-muted small d-block">Estimated Total</span>
                            <div class="fs-4 fw-bold text-primary" id="estimateTotalCost">0 MWK</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">Queue Bulk Broadcast</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSelectAll(master) {
    const boxes = document.querySelectorAll('.cust-checkbox');
    boxes.forEach(b => b.checked = master.checked);
    updateEstimator();
}

function updateEstimator() {
    const text = document.getElementById('bulkMessageText').value || '';
    const charLen = text.length;
    const segments = Math.max(1, Math.ceil(charLen / 160));
    document.getElementById('charCounter').innerText = charLen + ' chars | ' + segments + ' segment' + (segments > 1 ? 's' : '');

    const selectedCount = document.querySelectorAll('.cust-checkbox:checked').length;
    const costPerSms = <?= $costPerSegment ?>;
    const totalCost = selectedCount * segments * costPerSms;

    document.getElementById('estimateRecipients').innerText = selectedCount + ' recipients';
    document.getElementById('estimateSegments').innerText = segments + ' segment' + (segments > 1 ? 's' : '');
    document.getElementById('estimateTotalCost').innerText = totalCost.toLocaleString() + ' MWK';
}
</script>

