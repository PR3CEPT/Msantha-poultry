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

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('/admin/sms') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> SMS Centre</a>
                <h1 class="h3 fw-bold mb-0 text-dark">SMS Notification Templates</h1>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
                    Dynamic Messages
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Customize automated messaging content dispatched on incubation intake, transfer, hatch completion, payments, and reminders.</p>
        </div>
    </div>

    <!-- Available Placeholders Reference Guide -->
    <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4">
        <h6 class="fw-bold mb-2"><i class="bi bi-info-circle-fill me-2"></i>Available Dynamic Placeholders</h6>
        <div class="d-flex flex-wrap gap-2 small">
            <span class="badge bg-white text-dark border"><code>{eggs}</code> Eggs received/incubated</span>
            <span class="badge bg-white text-dark border"><code>{date}</code> Intake / event date</span>
            <span class="badge bg-white text-dark border"><code>{hatch_date}</code> Expected hatch date</span>
            <span class="badge bg-white text-dark border"><code>{cost}</code> Total service fee (MWK)</span>
            <span class="badge bg-white text-dark border"><code>{balance}</code> Outstanding balance (MWK)</span>
            <span class="badge bg-white text-dark border"><code>{amount}</code> Payment received (MWK)</span>
            <span class="badge bg-white text-dark border"><code>{pay_link}</code> PayChangu online payment URL</span>
            <span class="badge bg-white text-dark border"><code>{vaccine}</code> Vaccine name</span>
            <span class="badge bg-white text-dark border"><code>{batch}</code> Flock batch code</span>
            <span class="badge bg-white text-dark border"><code>{hatched}</code> Number of hatched chicks</span>
            <span class="badge bg-white text-dark border"><code>{percent}</code> Hatch rate percentage</span>
        </div>
    </div>

    <!-- Templates Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom p-3">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-chat-left-quote me-2 text-primary"></i>System Message Templates</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-3" style="width: 200px;">Template Code</th>
                        <th>Template Body & Placeholders</th>
                        <th class="text-center" style="width: 100px;">Segments</th>
                        <th class="text-center" style="width: 100px;">Status</th>
                        <th class="text-end pe-3" style="width: 120px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $t): ?>
                        <?php
                        $bodyLen = mb_strlen($t['body']);
                        $segments = max(1, (int) ceil($bodyLen / 160.0));
                        ?>
                        <tr>
                            <td class="ps-3">
                                <code class="fw-bold text-primary fs-6"><?= e($t['code']) ?></code>
                            </td>
                            <td>
                                <div class="text-dark"><?= e($t['body']) ?></div>
                                <small class="text-muted"><?= $bodyLen ?> characters</small>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $segments > 1 ? 'bg-warning text-dark' : 'bg-light text-secondary border' ?>">
                                    <?= $segments ?> seg
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= (int)$t['is_active'] === 1 ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= (int)$t['is_active'] === 1 ? 'Active' : 'Disabled' ?>
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal_<?= e($t['code']) ?>">
                                    <i class="bi bi-pencil me-1"></i>Edit
                                </button>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal_<?= e($t['code']) ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <form method="POST" action="<?= url('/admin/sms-templates') ?>" class="modal-content">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="code" value="<?= e($t['code']) ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Edit Template: <?= e($t['code']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Message Body</label>
                                            <textarea name="body" class="form-control" rows="4" maxlength="480" required><?= e($t['body']) ?></textarea>
                                            <small class="text-muted">Placeholders in <code>{...}</code> will be dynamically replaced when queuing.</small>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="act_<?= e($t['code']) ?>" <?= (int)$t['is_active'] === 1 ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="act_<?= e($t['code']) ?>">Template is active</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary fw-bold">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

