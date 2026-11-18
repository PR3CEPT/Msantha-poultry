<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\ReportService;

class ReportController
{
    private ReportService $reportService;

    public function __construct(?ReportService $reportService = null)
    {
        $this->reportService = $reportService ?? new ReportService();
    }

    /**
     * Reports Hub: Overview of all available analytics.
     */
    public function index(Request $request): Response
    {
        Auth::requirePermission('reports', 'view');
        Audit::logView('reports_hub');

        $year = (int) $request->query('year', (int) date('Y'));

        $pnl = $this->reportService->getMonthlyPnl($year);
        $feed = $this->reportService->getFeedCostEfficiency($year);
        $agedDebtors = $this->reportService->getAgedDebtors();

        $totalOverdue = 0;
        foreach ($agedDebtors as $d) {
            $totalOverdue += (int) $d['total_due'];
        }

        $format = $request->query('format');
        if ($format === 'csv') {
            return $this->exportPnlCsv($pnl, $year);
        }

        return View::render('reports/index', [
            'pageTitle' => 'Management Reports & Analytics',
            'activeNav' => 'reports',
            'year' => $year,
            'pnl' => $pnl,
            'feed' => $feed,
            'totalOverdue' => $totalOverdue,
            'debtorsCount' => count($agedDebtors),
        ]);
    }

    /**
     * Executive Decision & Business Performance Report:
     * Best sellers, worst sellers, high consumers, profit & loss, flock mortality financial loss.
     */
    public function performance(Request $request): Response
    {
        Auth::requirePermission('reports', 'view');
        Audit::logView('reports_performance');

        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));
        $year = $request->query('year') ? (int) $request->query('year') : null;

        $report = $this->reportService->getExecutivePerformanceReport($from ?: null, $to ?: null, $year);

        $format = $request->query('format');
        if ($format === 'csv') {
            return $this->exportPerformanceCsv($report);
        }
        if ($format === 'print' || $format === 'pdf') {
            return View::render('reports/print_document', [
                'pageTitle' => 'Executive Performance & Decision-Making Report',
                'reportType' => 'performance',
                'report' => $report,
                'from' => $from,
                'to' => $to,
                'year' => $year,
                'autoPrint' => ($format === 'pdf'),
                'csvUrl' => url('/reports/performance?format=csv' . ($from ? '&from=' . urlencode($from) : '') . ($to ? '&to=' . urlencode($to) : '') . ($year ? '&year=' . $year : '')),
            ], null);
        }

        return View::render('reports/performance', [
            'pageTitle' => 'Executive Decision & Business Performance',
            'activeNav' => 'reports',
            'report' => $report,
            'from' => $from,
            'to' => $to,
            'year' => $year,
        ]);
    }

    /**
     * Mortality per flock report.
     */
    public function mortality(Request $request): Response
    {
        Auth::requirePermission('reports', 'view');
        Audit::logView('reports_mortality');

        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));

        $flocks = $this->reportService->getMortalityPerFlock(null, $from ?: null, $to ?: null);

        if ($request->query('format') === 'csv') {
            return $this->exportMortalityCsv($flocks);
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $totalRows = count($flocks);
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        $paginatedFlocks = array_slice($flocks, ($page - 1) * $perPage, $perPage);

        return View::render('reports/mortality', [
            'pageTitle' => 'Flock Mortality % Analysis',
            'activeNav' => 'reports',
            'flocks' => $paginatedFlocks,
            'from' => $from,
            'to' => $to,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Hatch rate report.
     */
    public function hatch(Request $request): Response
    {
        Auth::requirePermission('reports', 'view');
        Audit::logView('reports_hatch');

        $groupBy = trim((string) $request->query('group_by', 'breed'));
        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));

        $data = $this->reportService->getHatchRateReport($groupBy, $from ?: null, $to ?: null);

        if ($request->query('format') === 'csv') {
            return $this->exportHatchCsv($data, $groupBy);
        }

        return View::render('reports/hatch', [
            'pageTitle' => 'Hatch Rate % Performance',
            'activeNav' => 'reports',
            'data' => $data,
            'groupBy' => $groupBy,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * Egg productivity report.
     */
    public function eggs(Request $request): Response
    {
        Auth::requirePermission('reports', 'view');
        Audit::logView('reports_eggs');

        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));

        $data = $this->reportService->getEggProductivityReport($from ?: null, $to ?: null);

        if ($request->query('format') === 'csv') {
            return $this->exportEggsCsv($data);
        }

        return View::render('reports/eggs', [
            'pageTitle' => 'Egg Production & Tray Yield',
            'activeNav' => 'reports',
            'data' => $data,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * Aged debtors report.
     */
    public function agedDebtors(Request $request): Response
    {
        Auth::requirePermission('reports', 'view');
        Audit::logView('reports_aged_debtors');

        $debtors = $this->reportService->getAgedDebtors();

        if ($request->query('format') === 'csv') {
            return $this->exportDebtorsCsv($debtors);
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $totalRows = count($debtors);
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        $paginatedDebtors = array_slice($debtors, ($page - 1) * $perPage, $perPage);

        $totals = [
            'tot30' => 0,
            'tot60' => 0,
            'tot90' => 0,
            'totOver' => 0,
            'grandTot' => 0,
        ];
        foreach ($debtors as $d) {
            $totals['tot30'] += $d['current_30'];
            $totals['tot60'] += $d['aged_31_60'];
            $totals['tot90'] += $d['aged_61_90'];
            $totals['totOver'] += $d['aged_over_90'];
            $totals['grandTot'] += $d['total_due'];
        }

        return View::render('reports/aged_debtors', [
            'pageTitle' => 'Aged Debtors & Outstanding Receivables',
            'activeNav' => 'reports',
            'debtors' => $paginatedDebtors,
            'totals' => $totals,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Commercial sales by item type report.
     */
    public function salesByItem(Request $request): Response
    {
        Auth::requirePermission('reports', 'view');
        Audit::logView('reports_sales_by_item');

        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));

        $items = $this->reportService->getSalesByItem($from ?: null, $to ?: null);

        if ($request->query('format') === 'csv') {
            return $this->exportSalesByItemCsv($items);
        }

        return View::render('reports/sales_by_item', [
            'pageTitle' => 'Sales Revenue by Product Line',
            'activeNav' => 'reports',
            'items' => $items,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * Incubation income by allocation category.
     */
    public function incubationAllocation(Request $request): Response
    {
        Auth::requirePermission('reports', 'view');
        Audit::logView('reports_incubation_allocation');

        $year = (int) $request->query('year', (int) date('Y'));
        $data = $this->reportService->getIncubationAllocationIncome($year);

        if ($request->query('format') === 'csv') {
            return $this->exportAllocationCsv($data);
        }

        return View::render('reports/incubation_allocation', [
            'pageTitle' => 'Incubation Income by Statutory Allocation (25/15/30/20/10)',
            'activeNav' => 'reports',
            'year' => $year,
            'data' => $data,
        ]);
    }

    /**
     * Feed cost efficiency report.
     */
    public function feedEfficiency(Request $request): Response
    {
        Auth::requirePermission('reports', 'view');
        Audit::logView('reports_feed_efficiency');

        $year = (int) $request->query('year', (int) date('Y'));
        $data = $this->reportService->getFeedCostEfficiency($year);

        $format = $request->query('format');
        if ($format === 'csv') {
            return $this->exportFeedEfficiencyCsv($data, $year);
        }

        return View::render('reports/feed_efficiency', [
            'pageTitle' => 'Feed Cost Efficiency per Bird & Egg',
            'activeNav' => 'reports',
            'year' => $year,
            'data' => $data,
        ]);
    }

    // ==========================================
    // CSV / Excel Export Helpers with Audit Metadata
    // ==========================================

    /**
     * Write standard corporate header into CSV stream with UTF-8 BOM.
     * Records who produced the report and when.
     */
    private function writeCsvHeader($out, string $reportTitle, ?string $periodDesc = null): void
    {
        // Add UTF-8 BOM so Microsoft Excel cleanly renders symbols and formatting
        fwrite($out, "\xEF\xBB\xBF");

        $user = Auth::user();
        $userName = $user['full_name'] ?? ($user['username'] ?? 'Authorized Staff');
        $userRole = strtoupper((string)Auth::role());
        $timestamp = date('d-M-Y H:i:s') . ' CAT';

        fputcsv($out, ['MSANTHA INVESTMENTS & GENERAL SUPPLIERS — POULTRY FARM MANAGEMENT']);
        fputcsv($out, ['Report Title:', $reportTitle]);
        fputcsv($out, ['Prepared By:', "{$userName} ({$userRole})"]);
        fputcsv($out, ['Generated On:', $timestamp]);
        if ($periodDesc) {
            fputcsv($out, ['Reporting Scope:', $periodDesc]);
        }
        fputcsv($out, []); // Blank separator line
    }

    private function exportPerformanceCsv(array $report): Response
    {
        $out = fopen('php://temp', 'r+');
        $this->writeCsvHeader($out, 'Executive Decision & Business Performance Report', $report['period_desc']);

        // 1. FINANCIAL SUMMARY SECTION
        fputcsv($out, ['--- 1. PROFIT & LOSS FINANCIAL PERFORMANCE ---']);
        fputcsv($out, ['Gross Commercial Revenue Inflow (MWK)', (int)$report['financials']['gross_revenue']]);
        fputcsv($out, ['Direct Operating Outflows (MWK)', (int)$report['financials']['operating_expenses']]);
        fputcsv($out, ['Flock Mortality Birds Lost', (int)$report['financials']['mortality_birds_lost']]);
        fputcsv($out, ['Flock Mortality Economic Loss (MWK)', (int)$report['financials']['mortality_loss_mwk']]);
        fputcsv($out, ['Net Operating Profit / Surplus (MWK)', (int)$report['financials']['net_profit']]);
        fputcsv($out, ['Net Operating Profit Margin %', $report['financials']['profit_margin_pct'] . '%']);
        fputcsv($out, []);

        // 2. PRODUCT LINE & BEST SELLERS
        fputcsv($out, ['--- 2. PRODUCT PERFORMANCE & BEST SELLERS ---']);
        fputcsv($out, ['Product Line', 'Orders / Batches', 'Units Sold', 'Unit Type', 'Average Price (MWK)', 'Total Revenue (MWK)', '% Share of Revenue', 'Performance Class']);
        foreach ($report['all_products'] as $idx => $p) {
            fputcsv($out, [
                $p['label'],
                $p['orders_count'],
                $p['total_units'],
                $p['unit_name'],
                $p['avg_price'],
                $p['total_revenue'],
                $p['share_pct'] . '%',
                $idx === 0 && $p['total_revenue'] > 0 ? 'Best Seller #1' : ($p['total_revenue'] > 0 ? 'Active Revenue' : 'Slow / Zero Movement'),
            ]);
        }
        fputcsv($out, []);

        // 3. HIGH CONSUMERS & TOP CUSTOMERS
        fputcsv($out, ['--- 3. HIGH CONSUMERS & TOP 10 CUSTOMERS ---']);
        fputcsv($out, ['Customer Name', 'Contact Phone', 'Location', 'POS Sales Orders', 'Incubation Batches', 'Total Contribution (MWK)', 'Outstanding Balance (MWK)']);
        foreach ($report['top_customers'] as $c) {
            fputcsv($out, [
                $c['name'],
                $c['phone'],
                $c['location'] ?? '—',
                $c['sales_orders'],
                $c['inc_batches'],
                $c['total_spend'],
                $c['balance_due'],
            ]);
        }
        fputcsv($out, []);

        // 4. ACTIONABLE INSIGHTS
        fputcsv($out, ['--- 4. MANAGEMENT DECISION-MAKING ACTION ITEMS ---']);
        foreach ($report['insights'] as $ins) {
            fputcsv($out, [$ins['title'], $ins['text']]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $filename = 'msantha_executive_performance_' . date('Ymd_His') . '.csv';
        return new Response((string)$csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function exportPnlCsv(array $pnl, int $year): Response
    {
        $out = fopen('php://temp', 'r+');
        $this->writeCsvHeader($out, "Monthly Profit & Loss Operating Statement", "Year {$year}");

        fputcsv($out, ['Month', 'Business Line', 'Total Receipts (MWK)', 'Total Expenses (MWK)', 'Net Profit / Surplus (MWK)']);
        foreach ($pnl['rows'] as $r) {
            fputcsv($out, [
                $r['month_key'],
                $r['unit_name'],
                $r['total_receipts'],
                $r['total_expenses'],
                $r['net_profit'],
            ]);
        }
        fputcsv($out, []);
        fputcsv($out, ['OVERALL TOTALS', 'All Business Units', $pnl['total_receipts'], $pnl['total_expenses'], $pnl['net_profit']]);
        fputcsv($out, ['POULTRY OPERATIONS ONLY', 'Poultry Unit', $pnl['poultry_receipts'], $pnl['poultry_expenses'], $pnl['poultry_profit']]);

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return new Response((string)$csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="msantha_monthly_pnl_' . $year . '.csv"',
        ]);
    }

    private function exportFeedEfficiencyCsv(array $feed, int $year): Response
    {
        $out = fopen('php://temp', 'r+');
        $this->writeCsvHeader($out, "Feed Cost Efficiency per Bird & Egg Report", "Year {$year}");

        fputcsv($out, ['Operational Metric', 'Recorded Value', 'Unit']);
        fputcsv($out, ['Total Feed Purchases Cost', (int)$feed['total_feed_cost'], 'MWK']);
        fputcsv($out, ['Total Birds Stocked / Intake', (int)$feed['total_birds_stocked'], 'Birds']);
        fputcsv($out, ['Total Eggs Produced', (int)$feed['total_eggs_produced'], 'Eggs']);
        fputcsv($out, ['Average Feed Cost Per Bird', (int)$feed['feed_cost_per_bird'], 'MWK / Bird']);
        fputcsv($out, ['Average Feed Cost Per Egg', (int)$feed['feed_cost_per_egg'], 'MWK / Egg']);

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return new Response((string)$csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="msantha_feed_efficiency_' . $year . '.csv"',
        ]);
    }

    private function exportMortalityCsv(array $flocks): Response
    {
        $out = fopen('php://temp', 'r+');
        $this->writeCsvHeader($out, 'Flock Mortality % Analysis Report');

        fputcsv($out, ['Batch Code', 'Breed', 'Start Date', 'Start Count', 'Total Deaths', 'Total Sold', 'Current Stock', 'Mortality Rate %', 'Status']);
        foreach ($flocks as $f) {
            fputcsv($out, [
                $f['batch_code'],
                $f['breed_name'],
                $f['start_date'],
                $f['start_count'],
                $f['total_deaths'],
                $f['total_sold'],
                $f['current_stock'],
                $f['mortality_rate'] . '%',
                $f['is_active'] ? 'Active' : 'Closed',
            ]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return new Response((string)$csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="msantha_flock_mortality_report.csv"',
        ]);
    }

    private function exportHatchCsv(array $data, string $groupBy): Response
    {
        $out = fopen('php://temp', 'r+');
        $this->writeCsvHeader($out, 'Incubation Hatch Rate % Performance Report', 'Grouped by ' . ucwords($groupBy));

        fputcsv($out, [ucwords($groupBy), 'Total Batches', 'Eggs Incubated', 'Infertile', 'Hatched Chicks', 'Hatch %']);
        foreach ($data as $r) {
            fputcsv($out, [
                $r['label'],
                $r['total_batches'],
                $r['total_incubated'],
                $r['total_infertile'],
                $r['total_hatched'],
                $r['hatch_rate'] . '%',
            ]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return new Response((string)$csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="msantha_hatch_rate_by_' . $groupBy . '.csv"',
        ]);
    }

    private function exportEggsCsv(array $data): Response
    {
        $out = fopen('php://temp', 'r+');
        $this->writeCsvHeader($out, 'Egg Production & Tray Yield Report');

        fputcsv($out, ['Month', 'Breed', 'Eggs Collected', 'Broken', 'Consumed', 'Sold', 'Incubated', 'Tray Yield (30s)']);
        foreach ($data as $r) {
            fputcsv($out, [
                $r['month'],
                $r['breed'],
                $r['collected'],
                $r['broken'],
                $r['consumed'],
                $r['sold'],
                $r['incubated'],
                $r['tray_equivalents'],
            ]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return new Response((string)$csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="msantha_egg_production_report.csv"',
        ]);
    }

    private function exportDebtorsCsv(array $debtors): Response
    {
        $out = fopen('php://temp', 'r+');
        $this->writeCsvHeader($out, 'Aged Debtors & Outstanding Customer Receivables');

        fputcsv($out, ['Customer Name', 'Mobile Phone', '0-30 Days (MWK)', '31-60 Days (MWK)', '61-90 Days (MWK)', 'Over 90 Days (MWK)', 'Total Overdue (MWK)']);
        foreach ($debtors as $d) {
            fputcsv($out, [
                $d['name'],
                $d['phone'],
                $d['current_30'],
                $d['aged_31_60'],
                $d['aged_61_90'],
                $d['aged_over_90'],
                $d['total_due'],
            ]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return new Response((string)$csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="msantha_aged_debtors_report.csv"',
        ]);
    }

    private function exportSalesByItemCsv(array $items): Response
    {
        $out = fopen('php://temp', 'r+');
        $this->writeCsvHeader($out, 'Sales Revenue by Product Line Report');

        fputcsv($out, ['Product Line', 'Order Count', 'Total Quantity Sold', 'Revenue (MWK)']);
        foreach ($items as $i) {
            fputcsv($out, [
                $i['label'],
                $i['orders_count'],
                $i['total_quantity'],
                $i['total_revenue'],
            ]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return new Response((string)$csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="msantha_sales_by_item_report.csv"',
        ]);
    }

    private function exportAllocationCsv(array $data): Response
    {
        $out = fopen('php://temp', 'r+');
        $this->writeCsvHeader($out, 'Incubation Statutory Allocation Income Report (25/15/30/20/10)');

        fputcsv($out, ['Month', 'Electricity (25%)', 'Water (15%)', 'Labour (30%)', 'Telephone (20%)', 'Profit (10%)', 'Total Allocated (MWK)']);
        foreach ($data['monthly_rows'] as $r) {
            fputcsv($out, [
                $r['month_key'],
                $r['electricity'],
                $r['water'],
                $r['labour'],
                $r['telephone'],
                $r['profit'],
                $r['total_month'],
            ]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return new Response((string)$csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="msantha_incubation_allocation_income.csv"',
        ]);
    }
}

