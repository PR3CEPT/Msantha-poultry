<?php declare(strict_types=1); ?>
<div class="row g-3">
    <!-- Header -->
    <div class="col-12 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
        <div>
            <h2 class="h4 fw-bold mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-chat-left-dots-fill text-success"></i>Web Inquiries &amp; Customer Messages
            </h2>
            <small class="text-muted">Inquiries submitted by farmers and customers through the public Msantha website</small>
        </div>
        <div>
            <a href="<?= url('/landing#contact') ?>" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-box-arrow-up-right me-1"></i>View Public Form
            </a>
        </div>
    </div>

    <!-- Feedback Flash -->
    <?php if (!empty($flashSuccess)): ?>
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm py-2.5 px-3 mb-0" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= e($flashSuccess) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm py-2.5 px-3 mb-0" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($flashError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- KPI Summary Cards -->
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Total Messages</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1"><?= number_format($counts['total']) ?></h3>
                </div>
                <div class="p-2 bg-primary-subtle rounded-circle text-primary">
                    <i class="bi bi-inbox-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-warning">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">New / Unattended</span>
                    <h3 class="fw-bold text-warning mb-0 mt-1"><?= number_format($counts['new']) ?></h3>
                </div>
                <div class="p-2 bg-warning-subtle rounded-circle text-warning">
                    <i class="bi bi-bell-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem;">Attended to</span>
                    <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($counts['attended']) ?></h3>
                </div>
                <div class="p-2 bg-success-subtle rounded-circle text-success">
                    <i class="bi bi-check-all fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Pills & Search -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <form method="GET" action="<?= url('/admin/inquiries') ?>" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search customer, phone, subject..." value="<?= e($search ?? '') ?>">
                    </div>
                </div>
                <div class="col-8 col-md-4">
                    <select name="status" class="form-select">
                        <option value="">All Inquiries (<?= $counts['total'] ?>)</option>
                        <option value="new" <?= ($statusFilter ?? '') === 'new' ? 'selected' : '' ?>>New / Awaiting Attention (<?= $counts['new'] ?>)</option>
                        <option value="attended" <?= ($statusFilter ?? '') === 'attended' ? 'selected' : '' ?>>Attended (<?= $counts['attended'] ?>)</option>
                    </select>
                </div>
                <div class="col-4 col-md-2 d-grid">
                    <button type="submit" class="btn btn-secondary fw-semibold">Filter</button>
                </div>
                <?php if ($search !== '' || $statusFilter !== ''): ?>
                    <div class="col-12 col-md-2">
                        <a href="<?= url('/admin/inquiries') ?>" class="btn btn-outline-secondary btn-sm w-100">Clear</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Inquiries List -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <?php if (empty($inquiries)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-chat-square-text display-5 d-block mb-3 text-secondary"></i>
                    <h5 class="fw-bold text-dark">No inquiries found</h5>
                    <p class="small text-muted mb-0">No customer messages match your current filter criteria.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-3 py-3" style="width: 130px;">Date &amp; Time</th>
                                <th class="py-3" style="width: 220px;">Customer Details</th>
                                <th class="py-3">Subject &amp; Message</th>
                                <th class="py-3" style="width: 140px;">Status</th>
                                <th class="pe-3 text-end py-3" style="width: 220px;">Manager Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inquiries as $inq): ?>
                                <?php
                                $isNew = ($inq['status'] === 'new');
                                $phoneDigits = preg_replace('/\D/', '', (string)$inq['phone']);
                                $waPhone = str_starts_with($phoneDigits, '0') ? '265' . substr($phoneDigits, 1) : $phoneDigits;
                                ?>
                                <tr class="<?= $isNew ? 'table-warning-subtle' : '' ?>">
                                    <td class="ps-3 small font-monospace">
                                        <strong class="text-dark d-block"><?= date('d-M-Y', strtotime($inq['created_at'])) ?></strong>
                                        <span class="text-muted"><?= date('H:i', strtotime($inq['created_at'])) ?></span>
                                    </td>
                                    <td>
                                        <strong class="text-dark d-block"><?= e($inq['name']) ?></strong>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <a href="tel:<?= e($inq['phone']) ?>" class="btn btn-xs btn-outline-success py-0 px-2 rounded-pill font-monospace" style="font-size: 0.72rem;" title="Call">
                                                <i class="bi bi-telephone-fill me-1"></i><?= e($inq['phone']) ?>
                                            </a>
                                            <a href="https://wa.me/<?= e($waPhone) ?>" target="_blank" class="btn btn-xs btn-success py-0 px-1.5 rounded-circle text-white" style="font-size: 0.7rem; width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center;" title="Chat on WhatsApp">
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="mb-1">
                                            <span class="badge bg-light text-dark border fw-semibold">
                                                <i class="bi bi-tag me-1 text-primary"></i><?= e($inq['subject']) ?>
                                            </span>
                                        </div>
                                        <div class="small text-secondary" style="max-width: 480px; word-break: break-word;">
                                            <?= nl2br(e($inq['message'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($isNew): ?>
                                            <span class="badge bg-warning text-dark border border-warning shadow-sm">
                                                <i class="bi bi-bell-fill me-1"></i>New / Awaiting
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="bi bi-check-circle-fill me-1"></i>Attended
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-3 text-end">
                                        <?php if ($isNew): ?>
                                            <button type="button" class="btn btn-sm btn-success fw-bold rounded-pill px-3 shadow-sm" data-bs-toggle="collapse" data-bs-target="#attendForm_<?= $inq['id'] ?>" aria-expanded="false">
                                                <i class="bi bi-check2 me-1"></i>Attend
                                            </button>
                                            <div class="collapse text-start mt-2" id="attendForm_<?= $inq['id'] ?>">
                                                <form method="POST" action="<?= url("/admin/inquiries/{$inq['id']}/attend") ?>" class="card p-2 bg-light border shadow-sm rounded-3">
                                                    <?= csrf_field() ?>
                                                    <label class="form-label small fw-bold text-dark mb-1">Resolution / Response Note (Optional):</label>
                                                    <textarea name="resolution_notes" class="form-control form-control-sm mb-2" rows="2" placeholder="e.g. Called customer and scheduled 500 eggs for Monday..."></textarea>
                                                    <div class="d-flex gap-1 justify-content-end">
                                                        <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2" data-bs-toggle="collapse" data-bs-target="#attendForm_<?= $inq['id'] ?>">Cancel</button>
                                                        <button type="submit" class="btn btn-xs btn-success fw-bold py-1 px-2.5">Confirm Attended</button>
                                                    </div>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-end">
                                                <small class="d-block text-success fw-semibold" style="font-size: 0.75rem;">
                                                    <i class="bi bi-person-check-fill me-1"></i><?= e($inq['attended_by_name'] ?? 'Staff') ?>
                                                </small>
                                                <?php if (!empty($inq['attended_at'])): ?>
                                                    <small class="d-block text-muted font-monospace" style="font-size: 0.68rem;">
                                                        <?= date('d-M H:i', strtotime($inq['attended_at'])) ?>
                                                    </small>
                                                <?php endif; ?>
                                                <?php if (!empty($inq['resolution_notes'])): ?>
                                                    <small class="d-block text-muted fst-italic mt-1" style="font-size: 0.7rem; max-width: 220px;" title="<?= e($inq['resolution_notes']) ?>">
                                                        "<?= e(substr($inq['resolution_notes'], 0, 50)) ?><?= strlen($inq['resolution_notes']) > 50 ? '...' : '' ?>"
                                                    </small>
                                                <?php endif; ?>
                                            </div>
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
</div>
