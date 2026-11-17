<?php declare(strict_types=1); ?>
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm p-4">
            <h3 class="h5 fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square text-success"></i>Edit User: <?= e($user['username']) ?>
            </h3>

            <!-- Active Device Session & Security Control -->
            <div class="card bg-light border-0 rounded-3 p-3 mb-4">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                    <div>
                        <div class="fw-bold small text-dark d-flex align-items-center gap-1.5 mb-1">
                            <i class="bi bi-shield-shaded text-primary"></i>Device Session Status
                        </div>
                        <?php if (!empty($userSession)): ?>
                            <div class="small text-muted" style="font-size: 0.78rem;">
                                <span class="badge bg-success-subtle text-success border border-success-subtle me-1">
                                    <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>Active Session
                                </span>
                                IP: <code class="text-dark"><?= e($userSession['ip']) ?></code> &bull; Last seen: <?= date('d-M H:i', strtotime($userSession['last_activity_at'])) ?>
                            </div>
                        <?php else: ?>
                            <div class="small text-muted" style="font-size: 0.78rem;">
                                <span class="badge bg-light text-muted border me-1">Offline</span>
                                No active device session currently recorded.
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($userSession) && $user['id'] != \App\Core\Auth::id()): ?>
                        <form method="POST" action="<?= url("/admin/users/{$user['id']}/force-logout") ?>" class="m-0">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-danger fw-semibold px-3" data-confirm="Terminate active session for '<?= e($user['username']) ?>'? This will disconnect their device immediately.">
                                <i class="bi bi-box-arrow-right me-1"></i>Force Logout User
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST" action="<?= url("/admin/users/{$user['id']}") ?>">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="full_name" class="form-label fw-semibold small text-muted">Full Name</label>
                    <input type="text" name="full_name" id="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required autofocus>
                </div>

                <?php if ($isMD ?? false): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="username" class="form-label fw-semibold small text-dark mb-0">
                                Username <span class="badge bg-warning-subtle text-dark border border-warning-subtle ms-1" style="font-size: 0.65rem;">MD Editable</span>
                            </label>
                            <?php if (!empty($pendingRequest)): ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem;">
                                    <i class="bi bi-bell-fill me-1"></i>Requested: @<?= e($pendingRequest['requested_username']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text">@</span>
                            <input type="text" name="username" id="username" class="form-control" value="<?= e($user['username']) ?>" minlength="3" maxlength="30" pattern="^[a-zA-Z0-9._]+$" required>
                            <?php if (!empty($pendingRequest)): ?>
                                <button class="btn btn-outline-success" type="button" onclick="document.getElementById('username').value='<?= e($pendingRequest['requested_username']) ?>'" title="Fill with requested username">
                                    <i class="bi bi-arrow-down-left-circle me-1"></i>Apply Requested
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($pendingRequest)): ?>
                            <div class="alert alert-warning py-2 px-3 mt-2 mb-0 small rounded-3">
                                <strong><i class="bi bi-chat-quote-fill me-1"></i>User Change Request:</strong>
                                User requested change to <strong>@<?= e($pendingRequest['requested_username']) ?></strong>.
                                <?php if (!empty($pendingRequest['reason'])): ?>
                                    <br><span class="text-muted">Reason given: "<?= e($pendingRequest['reason']) ?>"</span>
                                <?php endif; ?>
                                <br><small class="text-muted">Saving changes will update the username, approve the request, and record audit logs.</small>
                            </div>
                        <?php else: ?>
                            <small class="text-muted">As Managing Director, you can change staff usernames upon user request.</small>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold small text-muted">Username</label>
                        <input type="text" id="username" class="form-control bg-light" value="<?= e($user['username']) ?>" disabled>
                        <small class="text-muted">Only the Managing Director (owner) can change usernames upon user request.</small>
                    </div>
                <?php endif; ?>

                <?php if ($isMD ?? false): ?>
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold small text-dark mb-0">
                                <i class="bi bi-shield-lock-fill text-warning me-1"></i>Current Account Password (MD View Only)
                            </label>
                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size: 0.65rem;">Managing Director Vault</span>
                        </div>
                        <div class="input-group mt-2">
                            <input type="password" id="userPassView" class="form-control font-monospace bg-white" value="<?= e($userPasswordDisplay ?? '') ?>" placeholder="<?= empty($userPasswordDisplay) ? 'Pre-vault hash (Reset password below to make visible)' : '' ?>" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePass('userPassView', this)" title="Show/Hide Password">
                                <i class="bi bi-eye"></i>
                            </button>
                            <?php if (!empty($userPasswordDisplay)): ?>
                                <button class="btn btn-outline-primary" type="button" onclick="copyPasswordToClipboard('userPassView', this)" title="Copy Password">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($userPasswordDisplay)): ?>
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                <i class="bi bi-info-circle me-1"></i>This account was created prior to the vault system. Reset password below to reveal it here.
                            </small>
                        <?php else: ?>
                            <small class="text-success d-block mt-1" style="font-size: 0.72rem;">
                                <i class="bi bi-check-circle me-1"></i>Decrypted from executive security vault for Managing Director review.
                            </small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="role_id" class="form-label fw-semibold small text-muted">Role</label>
                    <select name="role_id" id="role_id" class="form-select" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= e($r['id']) ?>" <?= (int) $user['role_id'] === (int) $r['id'] ? 'selected' : '' ?>>
                                <?= e($r['name']) ?> (<?= e($r['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label fw-semibold small text-muted">Phone Number</label>
                    <input type="tel" name="phone" id="phone" class="form-control" inputmode="tel" value="<?= e($user['phone'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small text-muted">Email</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= e($user['email'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold small text-muted">Reset Password (leave empty to keep current)</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" minlength="8">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="bi bi-check-lg me-1"></i>Save Changes
                    </button>
                    <a href="<?= url('/admin/users') ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
            </form>
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
function copyPasswordToClipboard(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input || !input.value) return;
    navigator.clipboard.writeText(input.value).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2 text-success"></i>';
        setTimeout(() => { btn.innerHTML = orig; }, 1800);
    }).catch(() => { input.select(); document.execCommand('copy'); });
}
</script>

