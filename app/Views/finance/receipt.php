<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Voucher #<?= $entry['id'] ?> — Msantha Poultry</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #111; background: #f8f9fa; }
        .voucher-container { max-width: 720px; margin: 30px auto; padding: 35px; border: 1px solid #ddd; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .signature-line { border-bottom: 1px dashed #666; width: 180px; display: inline-block; height: 30px; }
        @media print {
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .voucher-container { border: none !important; padding: 0 !important; margin: 0 !important; box-shadow: none !important; max-width: 100% !important; }
        }
    </style>
</head>
<body>
    <div class="container voucher-container">
        <!-- Action Bar (hidden when printing) -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom no-print">
            <a href="<?= url('/finance') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Cash Books
            </a>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-sm btn-primary">
                    <i class="bi bi-printer me-1"></i> Print Voucher
                </button>
            </div>
        </div>

        <?php
        $isReceipt = (int)$entry['receipt'] > 0;
        $amount = $isReceipt ? (int)$entry['receipt'] : (int)$entry['expenditure'];
        $vchType = $isReceipt ? 'CASH RECEIPT VOUCHER' : 'PAYMENT VOUCHER';
        $vchBadgeClass = $isReceipt ? 'bg-success text-white' : 'bg-danger text-white';

        $ref = $entry['legacy_ref'] ?? $entry['reference'] ?? '';
        if ($ref && preg_match('/^[A-Z0-9_]+:row_(\d+)$/i', $ref, $m)) {
            $ref = 'REF-' . $m[1];
        }
        ?>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div class="d-flex align-items-center gap-3">
                <img src="<?= asset('assets/img/logo.png') ?>" alt="Msantha Poultry" style="width: 60px; height: 60px; object-fit: contain;">
                <div>
                    <h4 class="fw-bold mb-0 text-dark">Msantha Poultry Farm</h4>
                    <div class="text-muted small">MIGS &bull; Liwonde Station &bull; Enterprise Cash Ledger</div>
                </div>
            </div>
            <div class="text-end">
                <span class="badge <?= $vchBadgeClass ?> px-3 py-1.5 fw-bold text-uppercase fs-7 mb-1 d-inline-block">
                    <?= $vchType ?>
                </span>
                <div class="font-monospace fw-bold text-dark fs-6">
                    #VCH-<?= str_pad((string)$entry['id'], 5, '0', STR_PAD_LEFT) ?>
                </div>
            </div>
        </div>

        <!-- Metadata Grid -->
        <div class="row g-3 mb-4 small">
            <div class="col-6">
                <div class="text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Business Unit</div>
                <div class="fw-bold text-dark fs-6"><?= e($entry['unit_name']) ?> <span class="badge bg-light text-secondary border font-monospace small"><?= e($entry['unit_code']) ?></span></div>
            </div>
            <div class="col-6 text-end">
                <div class="text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Transaction Date</div>
                <div class="fw-bold text-dark fs-6"><?= !empty($entry['entry_date']) ? date('d-F-Y', strtotime($entry['entry_date'])) : '—' ?></div>
            </div>
            <div class="col-6">
                <div class="text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Account / Category</div>
                <div class="fw-semibold text-dark"><?= e($entry['category_name'] ?: 'General Operating Entry') ?></div>
            </div>
            <div class="col-6 text-end">
                <div class="text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Reference / Ref Code</div>
                <div class="font-monospace fw-semibold text-dark"><?= e($ref ?: '—') ?></div>
            </div>
        </div>

        <!-- Description & Breakdown Table -->
        <table class="table table-bordered mb-4 align-middle">
            <thead class="table-light small text-uppercase font-monospace">
                <tr>
                    <th class="py-2">Particulars / Description</th>
                    <th class="py-2 text-center" style="width: 140px;">Type</th>
                    <th class="py-2 text-end" style="width: 170px;">Amount (MWK)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="py-3">
                        <div class="fw-bold text-dark"><?= e($entry['description']) ?></div>
                        <?php if (!empty($entry['notes'])): ?>
                            <small class="text-muted d-block mt-1"><?= e($entry['notes']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center py-3">
                        <?php if ($isReceipt): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Receipt (Inflow)</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Disbursement (Outflow)</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end py-3 font-monospace fw-bold fs-5 <?= $isReceipt ? 'text-success' : 'text-danger' ?>">
                        <?= $isReceipt ? '+' : '-' ?><?= number_format($amount) ?> <small class="fs-7 fw-normal">MWK</small>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="table-light">
                    <td colspan="2" class="text-end fw-bold text-uppercase small py-2.5">Total Authorized Transaction</td>
                    <td class="text-end font-monospace fw-bold fs-5 py-2.5 text-dark">
                        <?= number_format($amount) ?> MWK
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Sign-Off Section -->
        <div class="row pt-4 mt-4 border-top text-center small text-muted">
            <div class="col-4">
                <div class="signature-line mb-2"></div>
                <div><strong>Prepared By</strong></div>
                <div style="font-size: 0.75rem;"><?= e($entry['cashier_name'] ?: 'Authorized Staff') ?></div>
            </div>
            <div class="col-4">
                <div class="signature-line mb-2"></div>
                <div><strong>Verified / Accounts</strong></div>
                <div style="font-size: 0.75rem;">Accountant</div>
            </div>
            <div class="col-4">
                <div class="signature-line mb-2"></div>
                <div><strong>Approved By</strong></div>
                <div style="font-size: 0.75rem;">MD / Farm Manager</div>
            </div>
        </div>

        <!-- Footer Notice -->
        <div class="text-center text-muted small mt-4 pt-3 border-top" style="font-size: 0.72rem;">
            Generated from Msantha Poultry Farm Manager &bull; Cash Book Ledger &bull; Liwonde Station
        </div>
    </div>
</body>
</html>
