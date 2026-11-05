<?php declare(strict_types=1); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden text-center p-4 p-md-5">
                <?php if ($result && $result['verified']): ?>
                    <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                        <i class="bi bi-check-circle-fill fs-1"></i>
                    </div>
                    <h2 class="h3 fw-bold text-success mb-2">Payment Confirmed!</h2>
                    <p class="text-muted mb-4">Your PayChangu mobile money / card payment was verified and credited successfully.</p>

                    <div class="bg-light rounded-3 p-3 text-start mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Reference:</span>
                            <code class="fw-bold text-dark"><?= e($txRef) ?></code>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Amount Paid:</span>
                            <span class="fw-bold text-success">+<?= number_format((int)$result['payment']['amount']) ?> MWK</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Status:</span>
                            <span class="badge bg-success">Success / Settled</span>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <?php if (!empty($result['payment']['purpose']) && $result['payment']['purpose'] === 'incubation_job'): ?>
                            <a href="<?= url('/incubation-jobs/' . $result['payment']['purpose_id']) ?>" class="btn btn-success fw-bold py-2">
                                <i class="bi bi-box-seam me-1"></i>Return to Incubation Batch
                            </a>
                        <?php else: ?>
                            <a href="<?= url('/dashboard') ?>" class="btn btn-primary fw-bold py-2">
                                <i class="bi bi-house me-1"></i>Back to Dashboard
                            </a>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <div class="rounded-circle bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                        <i class="bi bi-clock-history fs-1"></i>
                    </div>
                    <h2 class="h3 fw-bold text-dark mb-2">Checking Payment...</h2>
                    <p class="text-muted mb-4">
                        We are verifying your transaction with PayChangu. If you completed your mobile money prompt or card authorization, your payment will settle shortly.
                    </p>

                    <?php if (!empty($result['error'])): ?>
                        <div class="alert alert-warning text-start small mb-4">
                            <i class="bi bi-exclamation-triangle me-1"></i><?= e($result['error']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-center gap-2 mb-4">
                        <a href="<?= url('/payments/return?tx_ref=' . urlencode($txRef)) ?>" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh Verification
                        </a>
                        <a href="<?= url('/dashboard') ?>" class="btn btn-outline-secondary">
                            Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

