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
                            <label class="form-label fw-semibold text-dark">Trading Location / Area</label>
                            <input type="text" name="location" class="form-control" placeholder="e.g. Liwonde, Nsanama, Ulongwe">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch p-3 bg-light rounded-3 border">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="is_internal" value="1" id="isInternal">
                                    <label class="form-check-label fw-semibold text-dark" for="isInternal">
                                        Msantha Farm Internal
                                    </label>
                                    <small class="d-block text-muted">Tick if own farm eggs</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch p-3 bg-light rounded-3 border">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="sms_opt_in" value="1" id="smsOptIn" checked>
                                    <label class="form-check-label fw-semibold text-dark" for="smsOptIn">
                                        SMS Notifications
                                    </label>
                                    <small class="d-block text-muted">Receive hatch & fee alerts</small>
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

