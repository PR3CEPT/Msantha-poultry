<?php declare(strict_types=1); ?>
<div class="row justify-content-center py-5">
    <div class="col-12 col-md-6 text-center">
        <div class="card border-0 shadow-sm p-4">
            <div class="display-1 fw-bold text-success mb-2">404</div>
            <h3 class="fw-bold mb-3">Page Not Found</h3>
            <p class="text-muted mb-4">The page or resource you requested (<code><?= e($path ?? '') ?></code>) does not exist or has been moved.</p>
            <div>
                <a href="<?= url('/') ?>" class="btn btn-primary px-4">
                    <i class="bi bi-house-door me-2"></i>Return Home
                </a>
            </div>
        </div>
    </div>
</div>

