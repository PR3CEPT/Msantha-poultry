<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Receipt #<?= $sale['id'] ?> — Msantha Poultry</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #111; background: #fff; }
        .receipt-container { max-width: 650px; margin: 30px auto; padding: 30px; border: 1px dashed #ccc; }
        @media print {
            .no-print { display: none !important; }
            .receipt-container { border: none; padding: 0; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="container receipt-container">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <img src="<?= asset('assets/img/logo.png') ?>" alt="Msantha Poultry" style="width: 55px; height: 55px; object-fit: contain;">
                <div>
                    <h4 class="fw-bold mb-0">Msantha Poultry</h4>
                    <small class="text-muted">Poultry Farm & Commercial Offloads &middot; Malawi</small>
                </div>
            </div>
            <div class="text-end">
                <h5 class="fw-bold text-dark mb-0">SALES INVOICE / RECEIPT</h5>
                <small class="text-muted">Sale #<?= $sale['id'] ?></small>
            </div>
        </div>

        <div class="row mb-3 small">
            <div class="col-6">
                <strong>Customer:</strong> <?= e($sale['customer_name'] ?? 'Cash Walk-in') ?><br>
                <?php if (!empty($sale['customer_phone'])): ?>
                    <strong>Phone:</strong> <?= e($sale['customer_phone']) ?><br>
                <?php endif; ?>
                <?php if (!empty($sale['customer_location'])): ?>
                    <strong>Location:</strong> <?= e($sale['customer_location']) ?><br>
                <?php endif; ?>
            </div>
            <div class="col-6 text-end">
                <strong>Sale Date:</strong> <?= date('d-M-Y', strtotime($sale['sale_date'])) ?><br>
                <strong>Payment Channel:</strong> <?= strtoupper(str_replace('_', ' ', $sale['payment_method'])) ?><br>
                <strong>Status:</strong> <?= $sale['payment_method'] === 'credit' ? '<span class="text-danger fw-bold">DEBTOR (CREDIT)</span>' : '<span class="text-success fw-bold">PAID</span>' ?>
            </div>
        </div>

        <table class="table table-bordered mb-3 small">
            <thead class="table-light">
                <tr>
                    <th>Item Description</th>
                    <th>Breed / Category</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark"><?= ucwords(str_replace('_', ' ', $item['item_type'])) ?></div>
                            <?php if (!empty($item['batch_code'])): ?>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.72rem;">
                                        <i class="bi bi-boxes me-1 text-success"></i>Batch: <?= e($item['batch_code']) ?>
                                    </span>
                                    <?php if (isset($item['age_weeks_at_sale']) && $item['age_weeks_at_sale'] !== null): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" style="font-size: 0.72rem;">
                                            <i class="bi bi-calendar-event me-1"></i>Age: <?= (int)$item['age_weeks_at_sale'] ?> wks (<?= (int)$item['age_days_at_sale'] ?>d &bull; <?= (int)$item['age_months_at_sale'] ?>mo)
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?= e($item['breed_name'] ?? 'General') ?></td>
                        <td class="text-end"><?= number_format((int)$item['quantity']) ?></td>
                        <td class="text-end"><?= number_format((int)$item['unit_price']) ?> MWK</td>
                        <td class="text-end fw-bold"><?= number_format((int)$item['amount']) ?> MWK</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Total Invoiced:</th>
                    <th class="text-end fs-6"><?= number_format((int)$sale['total']) ?> MWK</th>
                </tr>
                <tr>
                    <th colspan="4" class="text-end text-success">Amount Paid:</th>
                    <th class="text-end text-success"><?= number_format((int)$sale['paid']) ?> MWK</th>
                </tr>
                <?php if ((int)$sale['total'] - (int)$sale['paid'] > 0): ?>
                    <tr>
                        <th colspan="4" class="text-end text-danger">Debtor Balance Due:</th>
                        <th class="text-end text-danger"><?= number_format((int)$sale['total'] - (int)$sale['paid']) ?> MWK</th>
                    </tr>
                <?php endif; ?>
            </tfoot>
        </table>

        <div class="text-center pt-3 border-top small text-muted">
            <p class="mb-0">Thank you for supporting Msantha Poultry Farm!</p>
            <p class="mb-0">Sales & Customer Inquiries: +265 999 899 620</p>
        </div>

        <div class="mt-4 text-center no-print">
            <button onclick="window.print()" class="btn btn-primary fw-bold me-2">
                <i class="bi bi-printer"></i> Print Invoice
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary">Close</button>
        </div>
    </div>
</body>
</html>

