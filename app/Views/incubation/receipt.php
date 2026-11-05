<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?= $job['id'] ?> — Msantha Poultry</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #111; background: #fff; }
        .receipt-container { max-width: 600px; margin: 30px auto; padding: 25px; border: 1px dashed #ccc; }
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
                    <small class="text-muted">Hatchery & Incubation Services &middot; Malawi</small>
                </div>
            </div>
            <div class="text-end">
                <h5 class="fw-bold text-dark mb-0">OFFICIAL RECEIPT</h5>
                <small class="text-muted">Job #<?= $job['id'] ?></small>
            </div>
        </div>

        <div class="row mb-3 small">
            <div class="col-6">
                <strong>Customer Name:</strong> <?= e($job['customer_name']) ?><br>
                <strong>Phone:</strong> <?= e($job['customer_phone'] ?? '—') ?><br>
                <strong>Location:</strong> <?= e($job['customer_location'] ?? 'Malawi') ?>
            </div>
            <div class="col-6 text-end">
                <strong>Date Received:</strong> <?= !empty($job['date_received']) ? date('d-M-Y', strtotime($job['date_received'])) : '—' ?><br>
                <strong>Date Printed:</strong> <?= date('d-M-Y H:i') ?><br>
                <strong>Currency:</strong> MWK (Malawi Kwacha)
            </div>
        </div>

        <table class="table table-bordered mb-3 small">
            <thead class="table-light">
                <tr>
                    <th>Service Description</th>
                    <th class="text-end">Qty (Eggs)</th>
                    <th class="text-end">Rate / Egg</th>
                    <th class="text-end">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Custom Egg Incubation & Hatching Service</td>
                    <td class="text-end"><?= number_format((int)$job['eggs_incubated']) ?></td>
                    <td class="text-end"><?= number_format((int)$job['rate_per_egg']) ?> MWK</td>
                    <td class="text-end fw-bold"><?= number_format((int)$job['cost']) ?> MWK</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total Fee:</th>
                    <th class="text-end"><?= number_format((int)$job['cost']) ?> MWK</th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end text-success">Total Amount Paid:</th>
                    <th class="text-end text-success"><?= number_format((int)$job['total_paid']) ?> MWK</th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end <?= (int)$job['balance'] > 0 ? 'text-danger' : 'text-muted' ?>">Balance Due:</th>
                    <th class="text-end <?= (int)$job['balance'] > 0 ? 'text-danger' : 'text-muted' ?>">
                        <?= number_format((int)$job['balance']) ?> MWK
                    </th>
                </tr>
            </tfoot>
        </table>

        <?php if (!empty($payments)): ?>
            <h6 class="fw-bold small mb-2">Payment Details</h6>
            <table class="table table-sm table-bordered mb-3 small">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Method</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= date('d-M-Y', strtotime($p['paid_on'])) ?></td>
                            <td><?= strtoupper(str_replace('_', ' ', $p['method'])) ?></td>
                            <td class="text-end"><?= number_format((int)$p['amount']) ?> MWK</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="text-center pt-3 border-top small text-muted">
            <p class="mb-0">Thank you for choosing Msantha Poultry Hatchery Services!</p>
            <p class="mb-0">Enquiries / Collection Hotline: +265 999 899 620</p>
        </div>

        <div class="mt-4 text-center no-print">
            <button onclick="window.print()" class="btn btn-primary fw-bold me-2">
                <i class="bi bi-printer"></i> Print Receipt
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary">Close</button>
        </div>
    </div>
</body>
</html>

