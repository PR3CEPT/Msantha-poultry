<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Official Management Report') ?> — Msantha Poultry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8fafc;
            color: #0f172a;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 13px;
        }

        .document-sheet {
            max-width: 960px;
            margin: 20px auto;
            background: #ffffff;
            padding: 35px 45px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .action-bar {
            max-width: 960px;
            margin: 15px auto 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .letterhead-border {
            border-bottom: 3px solid #14532d;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .meta-box {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 22px;
            font-size: 12px;
        }

        table {
            font-size: 12px;
        }

        th {
            background-color: #f1f5f9 !important;
            font-weight: 600;
            color: #334155;
            border-bottom: 2px solid #cbd5e1 !important;
        }

        .table-totals {
            background-color: #f8fafc;
            font-weight: bold;
            border-top: 2px solid #94a3b8 !important;
        }

        .doc-footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            font-size: 11px;
            color: #64748b;
        }

        @media print {
            body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 10pt;
            }

            .action-bar, .no-print {
                display: none !important;
            }

            .document-sheet {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }

            .meta-box {
                background: #f8fafc !important;
                border: 1px solid #ccc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            th {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .card, .table {
                break-inside: avoid !important;
            }

            @page {
                size: A4 portrait;
                margin: 12mm 15mm;
            }
        }
    </style>
</head>
<body>

    <!-- Top Action Bar (Hidden on Print/PDF) -->
    <div class="action-bar no-print">
        <a href="<?= url('/reports') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Reports Hub
        </a>
        <div class="d-flex gap-2">
            <?php if (!empty($csvUrl)): ?>
                <a href="<?= e($csvUrl) ?>" class="btn btn-success btn-sm fw-bold">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export to Excel (CSV)
                </a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-primary btn-sm fw-bold shadow-sm">
                <i class="bi bi-printer-fill me-1"></i>Save as PDF / Print Document
            </button>
        </div>
    </div>

    <div class="document-sheet">
        <!-- Letterhead -->
        <div class="letterhead-border">
            <div class="d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center gap-3">
                    <div style="font-size: 38px; line-height: 1; color: #14532d;">
                        <i class="bi bi-egg-fried"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0 text-success text-uppercase" style="letter-spacing: -0.5px; font-size: 1.35rem;">
                            Msantha Investments & General Suppliers
                        </h3>
                        <div class="fw-semibold text-dark small">Poultry Hatchery, Broiler & Commercial Layers Operations</div>
                        <div class="text-muted" style="font-size: 11px;">
                            Liwonde Station &bull; Mawira Area by Chipamba Road &bull; Machinga District, Malawi &bull; +265 8888 996 20 / +265 9998 996 20
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold mb-1">
                        OFFICIAL MANAGEMENT REPORT
                    </span>
                    <div class="font-monospace text-muted" style="font-size: 10px;">
                        REF: REP-<?= strtoupper(substr(md5(($pageTitle ?? '') . date('YmdH')), 0, 8)) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Document Metadata Audit Box -->
        <div class="meta-box">
            <div class="row g-2">
                <div class="col-6 col-md-3">
                    <span class="text-muted d-block small">Document Name:</span>
                    <strong class="text-dark"><?= e($pageTitle ?? 'Executive Performance Report') ?></strong>
                </div>
                <div class="col-6 col-md-3">
                    <span class="text-muted d-block small">Prepared By:</span>
                    <strong class="text-dark"><?= e(\App\Core\Auth::user()['full_name'] ?? 'Authorized Staff') ?> (<?= strtoupper(e(\App\Core\Auth::role() ?? 'USER')) ?>)</strong>
                </div>
                <div class="col-6 col-md-3">
                    <span class="text-muted d-block small">Generated Timestamp:</span>
                    <strong class="text-dark font-monospace"><?= date('d-M-Y H:i') ?> CAT</strong>
                </div>
                <div class="col-6 col-md-3">
                    <span class="text-muted d-block small">Reporting Scope:</span>
                    <strong class="text-success"><?= e($report['period_desc'] ?? ($year ? "Year {$year}" : 'All Time to Date')) ?></strong>
                </div>
            </div>
        </div>

        <!-- Render Report Content Partial -->
        <?php if (($reportType ?? '') === 'performance'): ?>

            <!-- Executive Summary Cards -->
            <div class="row g-2 mb-3">
                <div class="col-3">
                    <div class="p-2.5 border rounded bg-light text-center">
                        <small class="text-muted text-uppercase d-block" style="font-size: 10px;">Gross Revenue</small>
                        <div class="fs-6 fw-bold text-dark font-monospace"><?= number_format((int)$report['financials']['gross_revenue']) ?> MWK</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="p-2.5 border rounded bg-light text-center">
                        <small class="text-muted text-uppercase d-block" style="font-size: 10px;">Operating Costs</small>
                        <div class="fs-6 fw-bold text-danger font-monospace"><?= number_format((int)$report['financials']['operating_expenses']) ?> MWK</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="p-2.5 border rounded bg-light text-center">
                        <small class="text-muted text-uppercase d-block" style="font-size: 10px;">Mortality Losses</small>
                        <div class="fs-6 fw-bold text-warning-emphasis font-monospace"><?= number_format((int)$report['financials']['mortality_loss_mwk']) ?> MWK</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="p-2.5 border rounded bg-light text-center">
                        <small class="text-muted text-uppercase d-block" style="font-size: 10px;">Net Profit Margin</small>
                        <div class="fs-6 fw-bold text-success font-monospace"><?= $report['financials']['profit_margin_pct'] ?>%</div>
                    </div>
                </div>
            </div>

            <!-- Best Sellers & Product Lines -->
            <div class="mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-1 mb-2">
                    <i class="bi bi-graph-up-arrow text-success me-1"></i>1. Product Performance & Best Sellers
                </h6>
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Product Line</th>
                            <th class="text-center">Orders / Batches</th>
                            <th class="text-end">Units Sold</th>
                            <th class="text-end">Avg Price</th>
                            <th class="text-end">Total Revenue (MWK)</th>
                            <th class="text-end">% Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['all_products'] as $p): ?>
                            <tr>
                                <td>
                                    <strong><?= e($p['label']) ?></strong>
                                    <?php if ($p['total_revenue'] === 0): ?>
                                        <span class="badge bg-secondary-subtle text-secondary small ms-1">Slow / Zero Movement</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center font-monospace"><?= number_format((int)$p['orders_count']) ?></td>
                                <td class="text-end font-monospace"><?= number_format((int)$p['total_units']) ?> <?= e($p['unit_name']) ?></td>
                                <td class="text-end font-monospace"><?= number_format((int)$p['avg_price']) ?> MWK</td>
                                <td class="text-end font-monospace fw-bold text-dark"><?= number_format((int)$p['total_revenue']) ?></td>
                                <td class="text-end font-monospace"><?= $p['share_pct'] ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- High Consumers / Top Customers -->
            <div class="mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-1 mb-2">
                    <i class="bi bi-people-fill text-primary me-1"></i>2. High Consumers / Top Customer Accounts
                </h6>
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Contact / Location</th>
                            <th class="text-center">Orders</th>
                            <th class="text-center">Incubations</th>
                            <th class="text-end">Total Contribution (MWK)</th>
                            <th class="text-end">Balance Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($report['top_customers'])): ?>
                            <tr><td colspan="6" class="text-center py-2 text-muted">No customer transactions found for this period.</td></tr>
                        <?php else: ?>
                            <?php foreach ($report['top_customers'] as $c): ?>
                                <tr>
                                    <td><strong><?= e($c['name']) ?></strong></td>
                                    <td class="text-muted small"><?= e($c['phone'] ?: 'No phone') ?><?= !empty($c['location']) ? ' (' . e($c['location']) . ')' : '' ?></td>
                                    <td class="text-center font-monospace"><?= number_format((int)$c['sales_orders']) ?></td>
                                    <td class="text-center font-monospace"><?= number_format((int)$c['inc_batches']) ?></td>
                                    <td class="text-end font-monospace fw-bold text-dark"><?= number_format((int)$c['total_spend']) ?> MWK</td>
                                    <td class="text-end font-monospace <?= (int)$c['balance_due'] > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                                        <?= (int)$c['balance_due'] > 0 ? number_format((int)$c['balance_due']) . ' MWK' : 'Settled' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Profits, Costs & Mortality Losses Breakdown -->
            <div class="mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-1 mb-2">
                    <i class="bi bi-cash-stack text-success me-1"></i>3. Profit, Operating Costs & Mortality Loss Breakdown
                </h6>
                <table class="table table-bordered table-sm mb-0">
                    <tbody>
                        <tr>
                            <td><strong>Total Gross Commercial Inflows:</strong> (Product sales & incubation receipts)</td>
                            <td class="text-end font-monospace fw-bold text-success fs-6"><?= number_format((int)$report['financials']['gross_revenue']) ?> MWK</td>
                        </tr>
                        <tr>
                            <td><strong>Direct Operating Outflows:</strong> (Feed purchases, medicines, electricity, labour, utilities)</td>
                            <td class="text-end font-monospace fw-bold text-danger"><?= number_format((int)$report['financials']['operating_expenses']) ?> MWK</td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Flock Mortality Economic Loss:</strong> 
                                <span class="text-muted">(<?= number_format((int)$report['financials']['mortality_birds_lost']) ?> birds lost &times; ~4,500 MWK estimated bird replacement value)</span>
                            </td>
                            <td class="text-end font-monospace fw-bold text-warning-emphasis"><?= number_format((int)$report['financials']['mortality_loss_mwk']) ?> MWK</td>
                        </tr>
                        <tr class="table-totals">
                            <td><strong class="text-dark">NET OPERATING PROFIT / SURPLUS:</strong></td>
                            <td class="text-end font-monospace fw-bold fs-6 <?= (int)$report['financials']['net_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= number_format((int)$report['financials']['net_profit']) ?> MWK (<?= $report['financials']['profit_margin_pct'] ?>%)
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Decision-Making Action Items & Problem Solving -->
            <?php if (!empty($report['insights'])): ?>
                <div class="mb-3">
                    <h6 class="fw-bold text-dark border-bottom pb-1 mb-2">
                        <i class="bi bi-lightbulb-fill text-warning me-1"></i>4. Executive Decision-Making Insights & Problem Solving
                    </h6>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($report['insights'] as $ins): ?>
                            <div class="p-2 border rounded bg-light" style="font-size: 11.5px;">
                                <strong class="text-dark"><?= e($ins['title']) ?>:</strong>
                                <span class="text-secondary"><?= e($ins['text']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <!-- Official Sign-off & Footer -->
        <div class="doc-footer">
            <div class="row align-items-center">
                <div class="col-8">
                    <div><strong>Msantha Poultry Farm Management System</strong> &bull; Liwonde Hatchery & Farm Ledger</div>
                    <div style="font-size: 10px;">Confidential internal operational record prepared for management audit and financial oversight.</div>
                </div>
                <div class="col-4 text-end">
                    <div class="border-top pt-2 d-inline-block text-center" style="min-width: 140px;">
                        <span class="small text-muted d-block" style="font-size: 10px;">Authorized Signature:</span>
                        <div style="height: 15px;"></div>
                        <strong class="text-dark"><?= e(\App\Core\Auth::user()['full_name'] ?? 'Management') ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($autoPrint)): ?>
        <script>
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 350);
            });
        </script>
    <?php endif; ?>
</body>
</html>
