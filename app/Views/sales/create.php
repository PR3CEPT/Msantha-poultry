<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h1 class="h5 fw-bold mb-0 text-dark">
                        <i class="bi bi-cart-check text-success me-2"></i>Record Commercial Sale
                    </h1>
                    <a href="<?= url('/sales') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="<?= url('/sales') ?>" id="saleForm">
                        <?= csrf_field() ?>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Sale Date <span class="text-danger">*</span></label>
                                <input type="date" name="sale_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Customer (Optional)</label>
                                <select name="customer_id" class="form-select">
                                    <option value="">-- Cash Walk-in Customer --</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['phone'] ?? '') ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="card bg-light border p-3 rounded-3 mb-4">
                            <label class="form-label fw-semibold text-dark mb-2">Payment Method / Terms</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="pmCash" value="cash" checked>
                                    <label class="form-check-label fw-semibold text-dark" for="pmCash">Cash</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="pmAirtel" value="airtel_money">
                                    <label class="form-check-label fw-semibold text-dark" for="pmAirtel">Airtel Money</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="pmTnm" value="tnm_mpamba">
                                    <label class="form-check-label fw-semibold text-dark" for="pmTnm">TNM Mpamba</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="pmBank" value="bank">
                                    <label class="form-check-label fw-semibold text-dark" for="pmBank">Bank Transfer</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="pmCredit" value="credit">
                                    <label class="form-check-label fw-bold text-danger" for="pmCredit">Credit (Creates Debtor)</label>
                                </div>
                            </div>
                        </div>

                        <!-- Line Items -->
                        <div class="card bg-light border p-3 rounded-3 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark mb-0">Sale Line Items</h6>
                                <button type="button" class="btn btn-sm btn-outline-success" id="addRowBtn">
                                    <i class="bi bi-plus-lg me-1"></i>Add Row
                                </button>
                            </div>

                            <div id="itemsContainer">
                                <div class="item-row row g-2 mb-2 p-2 bg-white rounded-2 border">
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Item Type</label>
                                        <select name="item_type[]" class="form-select form-select-sm" required>
                                            <option value="live_birds">Live Birds</option>
                                            <option value="table_eggs">Table Eggs (Trays/Loose)</option>
                                            <option value="fertilised_eggs">Fertilised Eggs</option>
                                            <option value="stale_eggs">Stale / Crushed Eggs</option>
                                            <option value="chicks">Day-Old Chicks</option>
                                            <option value="manure">Organic Manure</option>
                                            <option value="other">Other Farm Produce</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Breed (Optional)</label>
                                        <select name="breed_id[]" class="form-select form-select-sm">
                                            <option value="">-- Any / Mixed --</option>
                                            <?php foreach ($breeds as $b): ?>
                                                <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Quantity</label>
                                        <input type="number" name="quantity[]" class="form-control form-control-sm qty-input" min="1" value="1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Unit Price (MWK)</label>
                                        <input type="number" name="unit_price[]" class="form-control form-control-sm price-input" min="0" value="8500" required>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3 bg-white rounded-3 border d-flex justify-content-between align-items-center mt-3">
                                <span class="fw-bold text-dark">Invoice Total:</span>
                                <span class="fs-4 fw-bold text-success" id="grandTotalDisplay">8,500 MWK</span>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success fw-bold flex-grow-1">
                                <i class="bi bi-check2-circle me-1"></i>Complete Sale & Issue Receipt
                            </button>
                            <a href="<?= url('/sales') ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('itemsContainer');
    const addBtn = document.getElementById('addRowBtn');
    const totalDisp = document.getElementById('grandTotalDisplay');

    function calculateTotal() {
        let sum = 0;
        const rows = container.querySelectorAll('.item-row');
        rows.forEach(r => {
            const qty = parseInt(r.querySelector('.qty-input').value, 10) || 0;
            const price = parseInt(r.querySelector('.price-input').value, 10) || 0;
            sum += (qty * price);
        });
        totalDisp.textContent = new Intl.NumberFormat().format(sum) + ' MWK';
    }

    addBtn.addEventListener('click', function() {
        const firstRow = container.querySelector('.item-row');
        const clone = firstRow.cloneNode(true);
        clone.querySelector('.qty-input').value = 1;
        clone.querySelector('.price-input').value = 5000;
        container.appendChild(clone);
        bindEvents(clone);
        calculateTotal();
    });

    function bindEvents(row) {
        row.querySelector('.qty-input').addEventListener('input', calculateTotal);
        row.querySelector('.price-input').addEventListener('input', calculateTotal);
    }

    container.querySelectorAll('.item-row').forEach(bindEvents);
    calculateTotal();
});
</script>

