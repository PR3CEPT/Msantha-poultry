<?php declare(strict_types=1); ?>
<div class="p-3 p-md-4">
    <!-- Profile Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
        <div class="p-4 p-md-5 text-white position-relative" style="background: linear-gradient(135deg, #14532d 0%, #1b6d39 60%, #22c55e 100%);">
            <div class="d-flex flex-column flex-md-row align-items-center gap-4 position-relative" style="z-index: 2;">
                <!-- Avatar with Initials -->
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-lg" 
                     style="width: 88px; height: 88px; font-size: 2rem; background: rgba(255,255,255,0.2); border: 3px solid rgba(255,255,255,0.4); backdrop-filter: blur(4px);">
                    <?= strtoupper(substr((string) ($user['full_name'] ?? 'U'), 0, 1)) ?>
                </div>
                <div class="text-center text-md-start">
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-start gap-2 mb-1">
                        <h1 class="h3 fw-bold mb-0 text-white"><?= e($user['full_name'] ?? 'User') ?></h1>
                        <span class="badge bg-warning text-dark fw-bold px-3 py-1 rounded-pill text-uppercase">
                            <i class="bi bi-shield-check me-1"></i><?= e($roleData['name'] ?? $user['role_code']) ?>
                        </span>
                    </div>
                    <p class="mb-1 text-white-50 small">
                        <i class="bi bi-person me-1"></i><strong>@<?= e($user['username']) ?></strong> &bull;
                        <i class="bi bi-telephone me-1"></i><?= e($user['phone'] ?? 'No phone set') ?> &bull;
                        <i class="bi bi-envelope me-1"></i><?= e($user['email'] ?? 'No email set') ?> &bull;
                        <i class="bi bi-geo-alt me-1"></i>Liwonde, Mawira Area by Chipamba Road, Machinga District
                    </p>
                    <small class="text-white-50">
                        <i class="bi bi-clock-history me-1"></i>Active Session ID: <code><?= substr(session_id(), 0, 8) ?>...</code>
                    </small>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="bg-light border-top px-4 py-2">
            <ul class="nav nav-pills gap-2" id="profileTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-semibold" id="details-tab" data-bs-toggle="pill" data-bs-target="#tab-details" type="button" role="tab">
                        <i class="bi bi-person-lines-fill me-1"></i>Personal Details
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold" id="security-tab" data-bs-toggle="pill" data-bs-target="#tab-security" type="button" role="tab">
                        <i class="bi bi-shield-lock me-1"></i>Password & Security
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold" id="activity-tab" data-bs-toggle="pill" data-bs-target="#tab-activity" type="button" role="tab">
                        <i class="bi bi-journal-text me-1"></i>Activity Log
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="profileTabContent">
        <!-- Tab 1: Personal Details -->
        <div class="tab-pane fade show active" id="tab-details" role="tabpanel">
            <div class="row g-4">
                <div class="col-12 col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-person-circle text-success me-2"></i>Edit Profile Details</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="<?= url('/profile') ?>">
                                <?= csrf_field() ?>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-dark small">Username</label>
                                    <input type="text" class="form-control bg-light" value="<?= e($user['username']) ?>" disabled readonly>
                                    <div class="form-text small">Your username is permanently set by farm administration.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="full_name" class="form-label fw-semibold text-dark small">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" id="full_name" class="form-control" required value="<?= e($user['full_name'] ?? '') ?>" placeholder="e.g. Isaac Chipeta">
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label fw-semibold text-dark small">Phone Number (Malawi Mobile)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted">+265</span>
                                        <input type="tel" name="phone" id="phone" class="form-control" value="<?= e(preg_replace('/^\+265/', '', (string)($user['phone'] ?? ''))) ?>" placeholder="e.g. 999899620">
                                    </div>
                                    <div class="form-text small">Used for farm password resets and operational SMS alerts (Airtel / TNM).</div>
                                </div>

                                <div class="mb-4">
                                    <label for="email" class="form-label fw-semibold text-dark small">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                                        <input type="email" name="email" id="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" placeholder="e.g. staff@msanthapoultry.mw">
                                    </div>
                                    <div class="form-text small">Used for executive reports, backup archives, and administrative notifications.</div>
                                </div>

                                <button type="submit" class="btn btn-success fw-bold px-4 py-2 shadow-sm">
                                    <i class="bi bi-check2-circle me-1"></i>Save Profile Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <!-- Role & Permissions Overview -->
                    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-award text-warning me-2"></i>Role & Authority</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="p-3 bg-success-subtle text-success rounded-3 fs-3">
                                    <i class="bi bi-shield-shaded"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0"><?= e($roleData['name'] ?? $user['role_code']) ?></h6>
                                    <small class="text-muted"><?= e($roleData['description'] ?? 'Standard role permissions') ?></small>
                                </div>
                            </div>
                            <hr class="my-3">
                            <div class="small">
                                <p class="fw-semibold text-dark mb-2">Operational Privileges:</p>
                                <ul class="list-unstyled mb-0 d-flex flex-column gap-2 text-muted">
                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Daily Flock Dynamics & Mortality Records</li>
                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Egg Collection & Tray Yield Entry</li>
                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Incubation Egg Receiving & Countdown</li>
                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Customer Registry & Egg Tracking</li>
                                    <?php if (in_array($user['role_code'], ['owner', 'manager'], true)): ?>
                                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Multi-Enterprise Cash Books (Poultry, Bee, Car Fleet)</li>
                                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Month-End Accounting Close & P&L</li>
                                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Data Quality Review & Typo Corrections</li>
                                    <?php endif; ?>
                                    <?php if ($user['role_code'] === 'owner'): ?>
                                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>Full Admin Rights & Database Backups</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Enterprise Branch Information -->
                    <div class="card border-0 shadow-sm rounded-4 bg-white">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-building text-primary me-2"></i>Msantha Investments (MIGS)</h6>
                            <p class="text-muted small mb-1">Liwonde, Mawira Area along Chipamba Road, Machinga District</p>
                            <p class="text-muted small mb-0">P.O. Box 250, Liwonde &bull; Phones: +265 8888 996 20 / +265 9998 996 20</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Password & Security -->
        <div class="tab-pane fade" id="tab-security" role="tabpanel">
            <div class="row g-4">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 bg-white">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-key text-success me-2"></i>Change Account Password</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="<?= url('/profile/password') ?>">
                                <?= csrf_field() ?>

                                <div class="mb-3">
                                    <label for="current_password" class="form-label fw-semibold text-dark small">Current Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" name="current_password" id="current_password" class="form-control" required placeholder="Enter current password">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePass('current_password', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label fw-semibold text-dark small">New Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" name="new_password" id="new_password" class="form-control" minlength="8" required placeholder="At least 8 characters">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePass('new_password', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text small">Must be at least 8 characters. Uses secure Argon2id cryptographic hashing.</div>
                                </div>

                                <div class="mb-4">
                                    <label for="new_password_confirmation" class="form-label fw-semibold text-dark small">Confirm New Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" minlength="8" required placeholder="Repeat new password">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePass('new_password_confirmation', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary fw-bold px-4 py-2 shadow-sm">
                                    <i class="bi bi-shield-check me-1"></i>Update Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shield-lock-fill text-success me-2"></i>Farm Security Protocols</h6>
                        <ul class="list-unstyled d-flex flex-column gap-3 small text-muted mb-0">
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-check2-circle text-success fs-5"></i>
                                <span><strong>Single Active Session:</strong> Signing in from another phone or browser automatically expires older sessions to protect farm financial records.</span>
                            </li>
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-check2-circle text-success fs-5"></i>
                                <span><strong>Brute-Force Lockout:</strong> 5 consecutive wrong password attempts triggers an automatic 15-minute lock on this account.</span>
                            </li>
                            <li class="d-flex align-items-start gap-2">
                                <i class="bi bi-check2-circle text-success fs-5"></i>
                                <span><strong>Audit Trail:</strong> Every password change, egg entry, and cash movement is logged with your User ID and timestamp.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 3: Activity Log -->
        <div class="tab-pane fade" id="tab-activity" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-primary me-2"></i>My Recent System Activity</h5>
                    <span class="badge bg-light text-muted border">Last 15 Actions</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($auditLogs)): ?>
                        <div class="py-5 text-center text-muted">
                            <i class="bi bi-journal-x display-4 d-block mb-2 text-muted"></i>
                            <p class="mb-0">No recorded actions yet in your session history.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase small text-muted">
                                    <tr>
                                        <th class="ps-4">Timestamp</th>
                                        <th>Action</th>
                                        <th>Target Table</th>
                                        <th>Record ID</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($auditLogs as $log): ?>
                                        <tr>
                                            <td class="ps-4 text-nowrap small text-muted"><?= e($log['created_at']) ?></td>
                                            <td>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace">
                                                    <?= e($log['action']) ?>
                                                </span>
                                            </td>
                                            <td class="font-monospace text-dark small"><?= e($log['table_name']) ?></td>
                                            <td><span class="badge bg-light text-dark border">#<?= (int) $log['record_id'] ?></span></td>
                                            <td class="small text-muted font-monospace"><?= e($log['ip_address'] ?? '127.0.0.1') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePass(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Auto-switch to tab if hash is in URL (e.g. #security)
document.addEventListener('DOMContentLoaded', () => {
    if (window.location.hash === '#security') {
        const securityTab = document.getElementById('security-tab');
        if (securityTab) {
            bootstrap.Tab.getOrCreateInstance(securityTab).show();
        }
    } else if (window.location.hash === '#activity') {
        const actTab = document.getElementById('activity-tab');
        if (actTab) {
            bootstrap.Tab.getOrCreateInstance(actTab).show();
        }
    }
});
</script>

