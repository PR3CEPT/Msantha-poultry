<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="h5 fw-bold mb-0 text-dark">Register New Customer</h1>
                        <a href="<?= url('/customers') ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="<?= url('/customers') ?>">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Customer Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Kondwani Phiri">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Primary Phone (Malawi E.164)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                                <input type="text" name="phone" class="form-control" placeholder="0999... or +265...">
                            </div>
                            <small class="text-muted">Will be automatically normalized to +265 format.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Email Address (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="customer@gmail.com">
                            </div>
                            <small class="text-muted">For digital receipts, payment confirmations, and batch notifications.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-dark">Trading Location / Area</label>
                            <input type="text" name="location" class="form-control" placeholder="e.g. Liwonde, Nsanama, Ulongwe">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch p-2.5 bg-light rounded-3 border h-100">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="is_internal" value="1" id="isInternal">
                                    <label class="form-check-label fw-semibold text-dark small" for="isInternal">
                                        Msantha Internal
                                    </label>
                                    <small class="d-block text-muted" style="font-size: 0.72rem;">Own farm eggs</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch p-2.5 bg-light rounded-3 border h-100">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="sms_opt_in" value="1" id="smsOptIn" checked>
                                    <label class="form-check-label fw-semibold text-dark small" for="smsOptIn">
                                        SMS Alerts
                                    </label>
                                    <small class="d-block text-muted" style="font-size: 0.72rem;">Hatch & fee SMS</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch p-2.5 bg-light rounded-3 border h-100">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="email_opt_in" value="1" id="emailOptIn" checked>
                                    <label class="form-check-label fw-semibold text-dark small" for="emailOptIn">
                                        Email Alerts
                                    </label>
                                    <small class="d-block text-muted" style="font-size: 0.72rem;">Invoices & receipts</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-dark">Remarks / Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Customer preferences or notes..."></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary fw-bold flex-grow-1">
                                <i class="bi bi-person-check-fill me-1"></i>Save Customer
                            </button>
                            <a href="<?= url('/customers') ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

