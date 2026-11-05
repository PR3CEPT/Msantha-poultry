<?php declare(strict_types=1); ?>
<div class="row justify-content-center py-5">
    <div class="col-12 col-md-6 text-center">
        <div class="card border-0 shadow-sm p-4">
            <div class="display-1 fw-bold text-warning mb-2">419</div>
            <h3 class="fw-bold mb-3">Session Expired / Security Token Mismatch</h3>
            <p class="text-muted mb-4">Your request could not be authenticated because your security CSRF token has expired or is invalid. Please refresh the page and try again.</p>
            <div>
                <a href="javascript:history.back()" class="btn btn-primary px-4 me-2">
                    <i class="bi bi-arrow-left me-1"></i>Go Back
                </a>
                <a href="<?= url('/login') ?>" class="btn btn-outline-secondary px-4">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Login Again
                </a>
            </div>
        </div>
    </div>
</div>

