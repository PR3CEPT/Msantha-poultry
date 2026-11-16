<?php declare(strict_types=1); ?>

<div class="p-3 p-md-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('/finance') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Cash Book</a>
                <h1 class="h3 fw-bold mb-0 text-dark">Multi-Business Profit & Loss</h1>
                <span class="badge bg-primary fs-6"><?= $year ?></span>
            </div>
            <p class="text-muted small mb-0 mt-1">Consolidated monthly revenue, expense, and net margin across poultry, bees, and fleet operations.</p>
        </div>

        <div class="d-flex gap-2">
            <!-- Year selector -->
            <form method="GET" action="<?= url('/finance/profit-and-loss') ?>" class="d-flex gap-2 align-items-center">
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php for ($y = 2026; $y >= 2022; $y--): ?>
                        <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>
            <button onclick="window.print()" class="btn btn-outline-dark btn-sm"><i class="bi bi-printer me-1"></i>Print P&L</button>
        </div>
    </div>

    <!-- Owner Dashboard Cards Per Business Unit -->
    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-briefcase me-2 text-primary"></i>Business Line Performance (<?= $year ?>)</h5>
    <div class="row g-3 mb-4">
        <?php foreach ($pnl['unit_cards'] as $card): ?>
            <?php
            $isProfit = $card['net'] >= 0;
            ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-top border-4 <?= $isProfit ? 'border-success' : 'border-danger' ?>">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="fw-bold text-dark mb-0"><?= e($card['name']) ?></h6>
                        <span class="badge bg-light text-secondary border"><?= e($card['code']) ?></span>
                    </div>
                    <div class="row g-2 my-2 text-center font-monospace">
                        <div class="col-6">
                            <small class="text-muted text-uppercase d-block" style="font-size: 0.7rem; font-family: inherit;">Inflows</small>
                            <span class="fw-bold text-success">+<?= number_format($card['receipts']) ?></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted text-uppercase d-block" style="font-size: 0.7rem; font-family: inherit;">Outflows</small>
                            <span class="fw-bold text-danger">-<?= number_format($card['expenses']) ?></span>
                        </div>
                    </div>
                    <div class="border-top pt-2 mt-2 d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Net Profit / Loss:</span>
                        <span class="fw-bold font-monospace <?= $isProfit ? 'text-success' : 'text-danger' ?>">
                            <?= $isProfit ? '+' : '' ?><?= number_format($card['net']) ?> MWK
                        </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- POULTRY Incubation Allocation Breakdown -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden bg-white">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0 fs-6"><i class="bi bi-pie-chart me-2 text-success"></i>Incubation Receipts Allocation (POULTRY 25/15/30/20/10)</h5>
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">Statutory Cost Split</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-monospace">
                <thead class="table-light text-muted small text-uppercase" style="font-size: 0.72rem;">
                    <tr>
                        <th class="ps-3 py-3" style="font-family: inherit;">Month</th>
                        <th class="text-end py-3">Electricity (25%)</th>
                        <th class="text-end py-3">Water (15%)</th>
                        <th class="text-end py-3">Labour (30%)</th>
                        <th class="text-end py-3">Telephone (20%)</th>
                        <th class="text-end py-3">Profit (10%)</th>
                        <th class="text-end pe-3 py-3">Total Allocated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pnl['incubation_allocations'])): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted font-sans-serif">No incubation allocations recorded for <?= $year ?>.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pnl['incubation_allocations'] as $month => $alloc): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-primary font-monospace"><?= e($month) ?></td>
                                <td class="text-end text-dark"><?= number_format((int)$alloc['electricity']) ?> MWK</td>
                                <td class="text-end text-dark"><?= number_format((int)$alloc['water']) ?> MWK</td>
                                <td class="text-end text-dark"><?= number_format((int)$alloc['labour']) ?> MWK</td>
                                <td class="text-end text-dark"><?= number_format((int)$alloc['telephone']) ?> MWK</td>
                                <td class="text-end text-success fw-bold"><?= number_format((int)$alloc['profit']) ?> MWK</td>
                                <td class="text-end pe-3 fw-bold text-primary"><?= number_format((int)$alloc['total_allocated']) ?> MWK</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Monthly Multi-Unit Summary Matrix -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0 fs-6"><i class="bi bi-calendar3 me-2 text-secondary"></i>Monthly Revenue & Expenditure Log</h5>
            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                Showing <?= count($pnl['monthly_rows']) ?> of <?= number_format($totalRows ?? count($pnl['monthly_rows'])) ?> Records
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-monospace">
                <thead class="table-light text-muted small text-uppercase" style="font-size: 0.72rem;">
                    <tr>
                        <th class="ps-3 py-3" style="font-family: inherit;">Month</th>
                        <th class="py-3" style="font-family: inherit;">Business Unit</th>
                        <th class="text-end py-3">Receipts</th>
                        <th class="text-end py-3">Expenditures</th>
                        <th class="text-end pe-3 py-3">Net Operating Income</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pnl['monthly_rows'])): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted font-sans-serif">No monthly transaction data available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pnl['monthly_rows'] as $row): ?>
                            <?php
                            $rec = (int)$row['total_receipts'];
                            $exp = (int)$row['total_expenses'];
                            $net = $rec - $exp;
                            ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark font-monospace"><?= e($row['month_key']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= e($row['unit_name']) ?></span></td>
                                <td class="text-end text-success fw-semibold"><?= $rec > 0 ? '+' . number_format($rec) . ' MWK' : '—' ?></td>
                                <td class="text-end text-danger fw-semibold"><?= $exp > 0 ? '-' . number_format($exp) . ' MWK' : '—' ?></td>
                                <td class="text-end pe-3 fw-bold <?= $net >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $net >= 0 ? '+' : '' ?><?= number_format($net) ?> MWK
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (!empty($totalPages) && $totalPages > 1): ?>
            <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center p-3">
                <span class="text-muted small">
                    Showing page <?= $page ?> of <?= $totalPages ?> (<?= number_format($totalRows) ?> total logs)
                </span>
                <nav aria-label="Monthly log navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('/finance/profit-and-loss?year=' . $year . '&page=' . ($page - 1)) ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        <li class="page-item active">
                            <span class="page-link"><?= $page ?></span>
                        </li>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('/finance/profit-and-loss?year=' . $year . '&page=' . ($page + 1)) ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>
