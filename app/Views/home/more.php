<?php declare(strict_types=1); ?>
<div class="p-3 p-md-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-0">All Farm Operations</h1>
            <small class="text-muted">Msantha Investments & General Suppliers (MIGS)</small>
        </div>
        <a href="<?= url('/profile') ?>" class="btn btn-outline-success btn-sm rounded-pill px-3">
            <i class="bi bi-person-circle me-1"></i>My Profile
        </a>
    </div>

    <!-- Quick Role Pill -->
    <?php $role = \App\Core\Auth::role(); ?>
    <div class="alert alert-light border shadow-sm rounded-4 d-flex align-items-center justify-content-between mb-4 py-2 px-3">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle text-uppercase"><?= e($role ?? 'USER') ?></span>
            <span class="small fw-semibold text-dark"><?= e(\App\Core\Auth::user()['full_name'] ?? 'User') ?></span>
        </div>
        <span class="badge bg-light text-muted border">Liwonde, Machinga</span>
    </div>

    <!-- Section 1: Core Operations -->
    <h6 class="text-uppercase text-muted fw-bold small mb-2 px-1">Core Operations</h6>
    <div class="row g-2 mb-4">
        <div class="col-6">
            <a href="<?= url('/') ?>" class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 h-100 bg-white">
                <i class="bi bi-speedometer2 text-success fs-3 mb-2"></i>
                <span class="fw-bold text-dark d-block">Dashboard</span>
                <small class="text-muted" style="font-size: 0.72rem;">Live flock & cash KPIs</small>
            </a>
        </div>
        <?php if (\App\Core\Auth::can('flocks', 'view')): ?>
            <div class="col-6">
                <a href="<?= url('/flocks') ?>" class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 h-100 bg-white">
                    <i class="bi bi-feather text-primary fs-3 mb-2"></i>
                    <span class="fw-bold text-dark d-block">Flock Dynamics</span>
                    <small class="text-muted" style="font-size: 0.72rem;">Daily mortality & counts</small>
                </a>
            </div>
        <?php endif; ?>
        <?php if (\App\Core\Auth::can('eggs', 'view')): ?>
            <div class="col-6">
                <a href="<?= url('/eggs') ?>" class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 h-100 bg-white">
                    <i class="bi bi-egg-fried text-warning fs-3 mb-2"></i>
                    <span class="fw-bold text-dark d-block">Egg Production</span>
                    <small class="text-muted" style="font-size: 0.72rem;">Trays & collections</small>
                </a>
            </div>
        <?php endif; ?>
        <?php if (\App\Core\Auth::can('incubation', 'view')): ?>
            <div class="col-6">
                <a href="<?= url('/incubation-jobs') ?>" class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 h-100 bg-white">
                    <i class="bi bi-thermometer-half text-info fs-3 mb-2"></i>
                    <span class="fw-bold text-dark d-block">Incubation</span>
                    <small class="text-muted" style="font-size: 0.72rem;">Batches & countdown</small>
                </a>
            </div>
        <?php endif; ?>
        <?php if (\App\Core\Auth::can('customers', 'view')): ?>
            <div class="col-6">
                <a href="<?= url('/customers') ?>" class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 h-100 bg-white">
                    <i class="bi bi-people text-success fs-3 mb-2"></i>
                    <span class="fw-bold text-dark d-block">Customers</span>
                    <small class="text-muted" style="font-size: 0.72rem;">Contacts & balances</small>
                </a>
            </div>
        <?php endif; ?>
        <?php if (\App\Core\Auth::can('sales', 'view')): ?>
            <div class="col-6">
                <a href="<?= url('/sales') ?>" class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 h-100 bg-white">
                    <i class="bi bi-cart-check text-danger fs-3 mb-2"></i>
                    <span class="fw-bold text-dark d-block">Farm Sales / POS</span>
                    <small class="text-muted" style="font-size: 0.72rem;">Point of sale & receipts</small>
                </a>
            </div>
        <?php endif; ?>
        <?php if (\App\Core\Auth::can('feed_drugs', 'view')): ?>
            <div class="col-12">
                <a href="<?= url('/feed-drugs') ?>" class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 bg-white d-flex flex-row align-items-center gap-3">
                    <i class="bi bi-capsule text-danger fs-3"></i>
                    <div>
                        <span class="fw-bold text-dark d-block">Feed & Vet Products</span>
                        <small class="text-muted" style="font-size: 0.72rem;">Vaccines, feed inventory & animal ear tags</small>
                    </div>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Section 2: Finance & Multi-Business -->
    <?php if (\App\Core\Auth::can('finance', 'view')): ?>
        <h6 class="text-uppercase text-muted fw-bold small mb-2 px-1">Enterprise Finance</h6>
        <div class="row g-2 mb-4">
            <div class="col-6">
                <a href="<?= url('/finance') ?>" class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 h-100 bg-white">
                    <i class="bi bi-cash-stack text-success fs-3 mb-2"></i>
                    <span class="fw-bold text-dark d-block">Cash Books</span>
                    <small class="text-muted" style="font-size: 0.72rem;">Poultry, Bee, Car hire</small>
                </a>
            </div>
            <div class="col-6">
                <a href="<?= url('/finance/pnl') ?>" class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 h-100 bg-white">
                    <i class="bi bi-graph-up-arrow text-primary fs-3 mb-2"></i>
                    <span class="fw-bold text-dark d-block">P&L Statements</span>
                    <small class="text-muted" style="font-size: 0.72rem;">Monthly net earnings</small>
                </a>
            </div>
            <div class="col-12">
                <a href="<?= url('/finance/reconciliation') ?>" class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 bg-white d-flex flex-row align-items-center gap-3">
                    <i class="bi bi-check2-all text-info fs-3"></i>
                    <div>
                        <span class="fw-bold text-dark d-block">Month Reconciliation</span>
                        <small class="text-muted" style="font-size: 0.72rem;">Reconcile with historical Excel month ends</small>
                    </div>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Section 3: Reports & Intelligence -->
    <?php if (\App\Core\Auth::can('reports', 'view')): ?>
        <h6 class="text-uppercase text-muted fw-bold small mb-2 px-1">Analytics & Reports</h6>
        <div class="row g-2 mb-4">
            <div class="col-6">
                <a href="<?= url('/reports') ?>" class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 h-100 bg-white">
                    <i class="bi bi-bar-chart-line text-primary fs-3 mb-2"></i>
                    <span class="fw-bold text-dark d-block">All Reports</span>
                    <small class="text-muted" style="font-size: 0.72rem;">Executive intelligence</small>
                </a>
            </div>
            <div class="col-6">
                <a href="<?= url('/reports/aged-debtors') ?>" class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 h-100 bg-white">
                    <i class="bi bi-hourglass-bottom text-warning fs-3 mb-2"></i>
                    <span class="fw-bold text-dark d-block">Aged Debtors</span>
                    <small class="text-muted" style="font-size: 0.72rem;">Receivables countdown</small>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Section 4: Administration (Owner/Manager) -->
    <?php if (in_array($role, ['owner', 'manager'], true)): ?>
        <h6 class="text-uppercase text-muted fw-bold small mb-2 px-1">Administration</h6>
        <div class="row g-2 mb-4">
            <?php if ($role === 'owner'): ?>
                <div class="col-6">
                    <a href="<?= url('/admin/users') ?>" class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 h-100 bg-white">
                        <i class="bi bi-shield-person text-success fs-3 mb-2"></i>
                        <span class="fw-bold text-dark d-block">User Accounts</span>
                        <small class="text-muted" style="font-size: 0.72rem;">Staff permissions</small>
                    </a>
                </div>
                <div class="col-6">
                    <a href="<?= url('/admin/backups') ?>" class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 h-100 bg-white">
                        <i class="bi bi-database-check text-primary fs-3 mb-2"></i>
                        <span class="fw-bold text-dark d-block">Backups</span>
                        <small class="text-muted" style="font-size: 0.72rem;">Database snapshots</small>
                    </a>
                </div>
            <?php endif; ?>
            <div class="col-6">
                <a href="<?= url('/admin/data-review') ?>" class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 h-100 bg-white">
                    <i class="bi bi-shield-check text-warning fs-3 mb-2"></i>
                    <span class="fw-bold text-dark d-block">Data Review</span>
                    <small class="text-muted" style="font-size: 0.72rem;">Fix Excel date typos</small>
                </a>
            </div>
            <div class="col-6">
                <a href="<?= url('/admin/import') ?>" class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 h-100 bg-white">
                    <i class="bi bi-box-arrow-in-down text-info fs-3 mb-2"></i>
                    <span class="fw-bold text-dark d-block">Data Import</span>
                    <small class="text-muted" style="font-size: 0.72rem;">CSV ingestion</small>
                </a>
            </div>
            <div class="col-12">
                <a href="<?= url('/admin/sms') ?>" class="card border-0 shadow-sm rounded-3 text-decoration-none p-3 bg-white d-flex flex-row align-items-center gap-3">
                    <i class="bi bi-chat-dots text-success fs-3"></i>
                    <div>
                        <span class="fw-bold text-dark d-block">SMS Notifications</span>
                        <small class="text-muted" style="font-size: 0.72rem;">Africa's Talking & customer alert logs</small>
                    </div>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Section 5: Account & Logout -->
    <h6 class="text-uppercase text-muted fw-bold small mb-2 px-1">My Account</h6>
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 mb-4">
        <a href="<?= url('/profile') ?>" class="d-flex align-items-center justify-content-between text-decoration-none text-dark py-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-person-circle fs-5 text-success"></i>
                <span class="fw-semibold">Edit My Profile</span>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
        </a>
        <a href="<?= url('/change-password') ?>" class="d-flex align-items-center justify-content-between text-decoration-none text-dark py-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-key fs-5 text-warning"></i>
                <span class="fw-semibold">Change Password</span>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
        </a>
        <form method="POST" action="<?= url('/logout') ?>" class="m-0 pt-2">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger w-100 fw-bold d-flex align-items-center justify-content-center gap-2 py-2">
                <i class="bi bi-box-arrow-right"></i>Sign Out
            </button>
        </form>
    </div>
</div>

