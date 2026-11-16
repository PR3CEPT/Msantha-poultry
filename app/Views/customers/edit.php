<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="h5 fw-bold mb-0 text-dark">Edit Customer</h1>
                        <a href="<?= url("/customers/{$customer['id']}") ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="<?= url("/customers/{$customer['id']}") ?>">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Customer Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="<?= e($customer['name']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Primary Phone (Malawi E.164)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                                <input type="text" name="phone" class="form-control" value="<?= e($customer['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Email Address (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="e.g. customer@example.com" value="<?= e($customer['email'] ?? '') ?>">
                            </div>
                            <div class="form-text small">For sending digital incubation receipts, payment confirmations, and invoices.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Trading Location / Area</label>
                            <input type="text" name="location" class="form-control" value="<?= e($customer['location'] ?? '') ?>">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-4">
                                <div class="form-check form-switch p-3 bg-light rounded-3 border h-100">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="is_internal" value="1" id="isInternal" <?= !empty($customer['is_internal']) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold text-dark small" for="isInternal">
                                        Msantha Internal
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-check form-switch p-3 bg-light rounded-3 border h-100">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="sms_opt_in" value="1" id="smsOptIn" <?= !empty($customer['sms_opt_in']) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold text-dark small" for="smsOptIn">
                                        SMS Notifications
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-check form-switch p-3 bg-light rounded-3 border h-100">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="email_opt_in" value="1" id="emailOptIn" <?= !empty($customer['email_opt_in']) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold text-dark small" for="emailOptIn">
                                        Email Notifications
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark">Remarks / Notes</label>
                            <textarea name="notes" class="form-control" rows="2"><?= e($customer['notes'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary fw-bold flex-grow-1">
                                <i class="bi bi-check2-circle me-1"></i>Update Customer
                            </button>
                            <a href="<?= url("/customers/{$customer['id']}") ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

