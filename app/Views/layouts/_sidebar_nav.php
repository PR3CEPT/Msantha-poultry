<?php declare(strict_types=1); ?>
<?php
$currentRole = \App\Core\Auth::role();
?>
<div class="nav flex-column gap-1">
    <div class="sidebar-brand-card shadow-sm d-none d-lg-block">
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-warning text-dark fw-bold px-2 py-0.5" style="font-size: 0.65rem;">MIGS &bull; 2006</span>
            <small class="text-white-50" style="font-size: 0.7rem;">Liwonde Station</small>
        </div>
        <div class="fw-bold text-white small lh-sm">Msantha Management</div>
        <div class="text-white-50" style="font-size: 0.72rem;">Livestock & Hatchery Portal</div>
    </div>

    <div class="sidebar-section-title">Core Operations</div>

    <a class="nav-link <?= ($activeNav ?? '') === 'home' ? 'active' : '' ?>" href="<?= url('/') ?>">
        <span class="nav-icon-wrap"><i class="bi bi-speedometer2 text-success"></i></span>
        <span>Executive Dashboard</span>
    </a>

    <?php if (\App\Core\Auth::can('flocks', 'view')): ?>
        <a class="nav-link <?= ($activeNav ?? '') === 'flocks' ? 'active' : '' ?>" href="<?= url('/flocks') ?>">
            <span class="nav-icon-wrap"><?= icon('chicken', 18) ?></span>
            <span>Flock Dynamics</span>
        </a>
    <?php endif; ?>

    <?php if (\App\Core\Auth::can('eggs', 'view')): ?>
        <a class="nav-link <?= ($activeNav ?? '') === 'eggs' ? 'active' : '' ?>" href="<?= url('/eggs') ?>">
            <span class="nav-icon-wrap"><?= icon('egg', 18) ?></span>
            <span>Egg Production</span>
        </a>
    <?php endif; ?>

    <?php if (\App\Core\Auth::can('incubation', 'view')): ?>
        <a class="nav-link <?= ($activeNav ?? '') === 'incubation' ? 'active' : '' ?>" href="<?= url('/incubation-jobs') ?>">
            <span class="nav-icon-wrap"><?= icon('incubator', 18) ?></span>
            <span>Incubation Pipeline</span>
        </a>
    <?php endif; ?>

    <?php if (\App\Core\Auth::can('customers', 'view')): ?>
        <a class="nav-link <?= ($activeNav ?? '') === 'customers' ? 'active' : '' ?>" href="<?= url('/customers') ?>">
            <span class="nav-icon-wrap"><i class="bi bi-people text-success"></i></span>
            <span>Customers & Balance</span>
        </a>
    <?php endif; ?>

    <?php if (\App\Core\Auth::can('sales', 'view')): ?>
        <a class="nav-link <?= ($activeNav ?? '') === 'sales' ? 'active' : '' ?>" href="<?= url('/sales') ?>">
            <span class="nav-icon-wrap"><i class="bi bi-cart-check text-danger"></i></span>
            <span>Farm Sales / POS</span>
        </a>
    <?php endif; ?>

    <!-- Supplies -->
    <?php if (\App\Core\Auth::can('feed_drugs', 'view')): ?>
        <div class="sidebar-section-title">Supplies & Health</div>
        <a class="nav-link <?= ($activeNav ?? '') === 'feed_drugs' ? 'active' : '' ?>" href="<?= url('/feed-drugs') ?>">
            <span class="nav-icon-wrap"><?= icon('feed', 18) ?></span>
            <span>Feed & Vet Products</span>
        </a>
    <?php endif; ?>

    <!-- Finance -->
    <?php if (\App\Core\Auth::can('finance', 'view')): ?>
        <div class="sidebar-section-title">Enterprise Finance</div>
        <a class="nav-link <?= ($activeNav ?? '') === 'finance' ? 'active' : '' ?>" href="<?= url('/finance') ?>">
            <span class="nav-icon-wrap"><?= icon('cash', 18) ?></span>
            <span>Cash Books (6 Units)</span>
        </a>
        <?php if (in_array($currentRole, ['owner', 'manager', 'accountant'], true)): ?>
            <a class="nav-link <?= ($activeNav ?? '') === 'finance_pnl' ? 'active' : '' ?>" href="<?= url('/finance/pnl') ?>">
                <span class="nav-icon-wrap"><i class="bi bi-graph-up-arrow text-primary"></i></span>
                <span>Monthly P&L Statements</span>
            </a>
            <a class="nav-link <?= ($activeNav ?? '') === 'finance_reconcile' ? 'active' : '' ?>" href="<?= url('/finance/reconciliation') ?>">
                <span class="nav-icon-wrap"><i class="bi bi-check2-all text-info"></i></span>
                <span>Month Reconciliation</span>
            </a>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Reports -->
    <?php if (\App\Core\Auth::can('reports', 'view')): ?>
        <div class="sidebar-section-title">Reports & Intelligence</div>
        <a class="nav-link <?= ($activeNav ?? '') === 'reports' ? 'active' : '' ?>" href="<?= url('/reports') ?>">
            <span class="nav-icon-wrap"><?= icon('report', 18) ?></span>
            <span>Executive Reports</span>
        </a>
        <a class="nav-link <?= ($activeNav ?? '') === 'reports_debtors' ? 'active' : '' ?>" href="<?= url('/reports/aged-debtors') ?>">
            <span class="nav-icon-wrap"><?= icon('debt', 18) ?></span>
            <span>Aged Debtors</span>
        </a>
    <?php endif; ?>

    <!-- Communications -->
    <?php if (\App\Core\Auth::can('sms', 'view')): ?>
        <div class="sidebar-section-title">Communications</div>
        <a class="nav-link <?= ($activeNav ?? '') === 'admin_sms' ? 'active' : '' ?>" href="<?= url('/admin/sms') ?>">
            <span class="nav-icon-wrap"><i class="bi bi-chat-dots text-success"></i></span>
            <span>SMS Notifications</span>
        </a>
    <?php endif; ?>

    <?php if (in_array($currentRole, ['owner', 'manager'], true)): ?>
        <a class="nav-link <?= ($activeNav ?? '') === 'admin_inquiries' ? 'active' : '' ?>" href="<?= url('/admin/inquiries') ?>">
            <span class="nav-icon-wrap"><i class="bi bi-chat-left-text text-warning"></i></span>
            <span>Web Inquiries</span>
        </a>
    <?php endif; ?>

    <!-- Administration -->
    <?php if (in_array($currentRole, ['owner', 'manager'], true)): ?>
        <div class="sidebar-section-title">Administration</div>
        <?php if ($currentRole === 'owner' || \App\Core\Auth::can('admin_users', 'view')): ?>
            <a class="nav-link <?= ($activeNav ?? '') === 'admin_users' ? 'active' : '' ?>" href="<?= url('/admin/users') ?>">
                <span class="nav-icon-wrap"><i class="bi bi-shield-person text-success"></i></span>
                <span>Staff Accounts</span>
            </a>
        <?php endif; ?>
        <?php if ($currentRole === 'owner'): ?>
            <a class="nav-link <?= ($activeNav ?? '') === 'admin_backups' ? 'active' : '' ?>" href="<?= url('/admin/backups') ?>">
                <span class="nav-icon-wrap"><?= icon('backup', 18) ?></span>
                <span>Database Backups</span>
            </a>
        <?php endif; ?>
        <a class="nav-link <?= ($activeNav ?? '') === 'admin_import' ? 'active' : '' ?>" href="<?= url('/admin/import') ?>">
            <span class="nav-icon-wrap"><i class="bi bi-box-arrow-in-down text-secondary"></i></span>
            <span>Data Import Center</span>
        </a>
        <?php if (\App\Core\Auth::can('admin_audit', 'view')): ?>
            <a class="nav-link <?= ($activeNav ?? '') === 'admin_audit' ? 'active' : '' ?>" href="<?= url('/admin/audit') ?>">
                <span class="nav-icon-wrap"><i class="bi bi-journal-text text-muted"></i></span>
                <span>Audit Trail Log</span>
            </a>
        <?php endif; ?>
    <?php endif; ?>

    <hr class="my-2 text-muted">
    <a class="nav-link text-muted small" href="<?= url('/landing') ?>" target="_blank">
        <span class="nav-icon-wrap"><i class="bi bi-globe text-primary"></i></span>
        <span>Public Website</span>
    </a>
</div>
