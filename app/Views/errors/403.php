<?php declare(strict_types=1); ?>
<div class="row justify-content-center py-5">
    <div class="col-12 col-md-6 text-center">
        <div class="card border-0 shadow-sm p-4">
            <div class="display-1 fw-bold text-danger mb-2">403</div>
            <h3 class="fw-bold mb-3">Access Denied</h3>
            <p class="text-muted mb-4"><?= e($message ?? 'You do not have permission to view or manage this module.') ?></p>
            <div>
                <a href="<?= url('/') ?>" class="btn btn-primary px-4">
                    <i class="bi bi-house-door me-2"></i>Return to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

