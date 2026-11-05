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
                <a href="<?= url('/admin/sms') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> SMS Centre</a>
                <h1 class="h3 fw-bold mb-0 text-dark">SMS Gateway Configuration</h1>
                <span class="badge bg-secondary fs-6">Owner Settings</span>
            </div>
            <p class="text-muted small mb-0 mt-1">Configure your SMS gateway adapter (Africa's Talking, Generic HTTP, or Mock/Fake for dev) and registered Sender ID.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Settings Form Column -->
        <div class="col-lg-8">
            <form method="POST" action="<?= url('/admin/settings/sms') ?>" class="card border-0 shadow-sm rounded-3">
                <?= csrf_field() ?>
                <div class="card-header bg-white border-bottom p-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-sliders me-2 text-primary"></i>Gateway Provider Selection</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Active SMS Provider</label>
                            <select name="sms_provider" class="form-select" id="providerSelect" onchange="toggleProviderTabs()">
                                <option value="fake" <?= ($settings['sms_provider'] ?? 'fake') === 'fake' ? 'selected' : '' ?>>Fake / Mock Provider (Testing & Local Dev)</option>
                                <option value="africas_talking" <?= ($settings['sms_provider'] ?? '') === 'africas_talking' ? 'selected' : '' ?>>Africa's Talking (Production / Sandbox)</option>
                                <option value="generic_http" <?= ($settings['sms_provider'] ?? '') === 'generic_http' ? 'selected' : '' ?>>Generic HTTP Gateway (Airtel / TNM / Custom)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Registered Sender ID</label>
                            <input type="text" name="sms_sender_id" class="form-control" value="<?= e($settings['sms_sender_id'] ?? 'MSANTHA') ?>" placeholder="e.g. MSANTHA">
                            <small class="text-muted">Registered alphanumeric sender name (max 11 chars)</small>
                        </div>

                        <!-- Africa's Talking Settings -->
                        <div id="africasTalkingSection" class="col-12 border-top pt-3 mt-3">
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-globe me-2"></i>Africa's Talking Credentials</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Username</label>
                                    <input type="text" name="africastalking_username" class="form-control" value="<?= e($settings['africastalking_username'] ?? '') ?>" placeholder="e.g. msanthapoultry or sandbox">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">API Key</label>
                                    <input type="password" name="africastalking_api_key" class="form-control" value="<?= !empty($settings['africastalking_api_key']) ? '********' : '' ?>" placeholder="Paste secret API key">
                                    <small class="text-muted">Secret key is masked for security and never printed in logs.</small>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="africastalking_sandbox" value="1" id="sandboxCheck" <?= !empty($settings['africastalking_sandbox']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="sandboxCheck">Use Africa's Talking Sandbox Environment</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Generic HTTP Settings -->
                        <div id="genericHttpSection" class="col-12 border-top pt-3 mt-3">
                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-hdd-network me-2"></i>Generic HTTP Gateway Configuration</h6>
                            <div class="row g-3">
                                <div class="col-md-9">
                                    <label class="form-label fw-semibold">Gateway Endpoint URL</label>
                                    <input type="url" name="sms_http_url" class="form-control" value="<?= e($settings['sms_http_url'] ?? '') ?>" placeholder="https://api.sms-gateway.com/send">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">HTTP Method</label>
                                    <select name="sms_http_method" class="form-select">
                                        <option value="POST" <?= ($settings['sms_http_method'] ?? 'POST') === 'POST' ? 'selected' : '' ?>>POST</option>
                                        <option value="GET" <?= ($settings['sms_http_method'] ?? '') === 'GET' ? 'selected' : '' ?>>GET</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">HTTP Headers (one per line)</label>
                                    <textarea name="sms_http_headers" class="form-control font-monospace small" rows="2" placeholder="Authorization: Bearer YOUR_TOKEN&#10;Content-Type: application/json"><?= e($settings['sms_http_headers'] ?? '') ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Body Template</label>
                                    <textarea name="sms_http_body_template" class="form-control font-monospace small" rows="2" placeholder='{"to":"{phone}","message":"{message}","sender":"{sender_id}"}'><?= e($settings['sms_http_body_template'] ?? '') ?></textarea>
                                    <small class="text-muted">Placeholders: <code>{phone}</code>, <code>{message}</code>, <code>{sender_id}</code></small>
                                </div>
                            </div>
                        </div>

                        <!-- General Farm Alert Phone & Cost Settings -->
                        <div class="col-12 border-top pt-3 mt-3">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-telephone me-2 text-secondary"></i>Alerts & Billing Parameters</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Farm Manager Alert Phone</label>
                                    <input type="text" name="sms_management_alert_phone" class="form-control" value="<?= e($settings['sms_management_alert_phone'] ?? '+265999899620') ?>" placeholder="+265999899620">
                                    <small class="text-muted">Destination phone for mortality alerts and due vaccines.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Cost per SMS Segment (MWK)</label>
                                    <input type="number" name="sms_cost_per_segment_mwk" class="form-control" value="<?= e($settings['sms_cost_per_segment_mwk'] ?? '25') ?>" min="1">
                                    <small class="text-muted">Used for bulk broadcast cost estimator (standard MK 25).</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white border-top p-3 text-end">
                    <button type="submit" class="btn btn-primary fw-bold">Save Gateway Configuration</button>
                </div>
            </form>
        </div>

        <!-- Test SMS Column -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white border-bottom p-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-send me-2 text-success"></i>Send Test SMS</h5>
                </div>
                <div class="card-body p-3">
                    <p class="text-muted small">Send an immediate test message using the currently active provider to verify gateway credentials and connectivity.</p>
                    <form method="POST" action="<?= url('/admin/settings/sms/test') ?>">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Recipient Mobile</label>
                            <input type="text" name="test_phone" class="form-control" placeholder="e.g. 0999899620 or +265999899620" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold">
                            <i class="bi bi-chat-dots me-1"></i>Dispatch Test SMS
                        </button>
                    </form>
                </div>
            </div>

            <!-- Delivery Webhook Info -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-bottom p-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-link-45deg me-2 text-info"></i>Delivery Report Webhook</h6>
                </div>
                <div class="card-body p-3">
                    <small class="text-muted d-block mb-2">Configure this endpoint in your SMS provider's portal to receive real-time delivery receipts:</small>
                    <code class="d-block p-2 bg-light rounded text-break user-select-all" style="font-size: 0.75rem;">
                        <?= url('/api/sms/delivery-report') ?>
                    </code>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleProviderTabs() {
    const prov = document.getElementById('providerSelect').value;
    const at = document.getElementById('africasTalkingSection');
    const gh = document.getElementById('genericHttpSection');
    
    if (prov === 'africas_talking') {
        at.style.display = 'block';
        gh.style.display = 'none';
    } else if (prov === 'generic_http') {
        at.style.display = 'none';
        gh.style.display = 'block';
    } else {
        at.style.display = 'none';
        gh.style.display = 'none';
    }
}
document.addEventListener('DOMContentLoaded', toggleProviderTabs);
</script>

