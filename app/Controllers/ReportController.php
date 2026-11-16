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

        return View::render('reports/feed_efficiency', [
            'pageTitle' => 'Feed Cost Efficiency per Bird & Egg',
            'activeNav' => 'reports',
            'year' => $year,
            'data' => $data,
        ]);
    }

    // ==========================================
    // CSV Export Helpers
    // ==========================================

    private function exportMortalityCsv(array $flocks): Response
    {
        $out = fopen('php://temp', 'r+');
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
            'Content-Disposition' => 'attachment; filename="flock_mortality_report.csv"',
        ]);
    }

    private function exportHatchCsv(array $data, string $groupBy): Response
    {
        $out = fopen('php://temp', 'r+');
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
            'Content-Disposition' => 'attachment; filename="hatch_rate_by_' . $groupBy . '.csv"',
        ]);
    }

    private function exportEggsCsv(array $data): Response
    {
        $out = fopen('php://temp', 'r+');
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
            'Content-Disposition' => 'attachment; filename="egg_production_report.csv"',
        ]);
    }

    private function exportDebtorsCsv(array $debtors): Response
    {
        $out = fopen('php://temp', 'r+');
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
            'Content-Disposition' => 'attachment; filename="aged_debtors_report.csv"',
        ]);
    }

    private function exportSalesByItemCsv(array $items): Response
    {
        $out = fopen('php://temp', 'r+');
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
            'Content-Disposition' => 'attachment; filename="sales_by_item_report.csv"',
        ]);
    }

    private function exportAllocationCsv(array $data): Response
    {
        $out = fopen('php://temp', 'r+');
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
            'Content-Disposition' => 'attachment; filename="incubation_allocation_income.csv"',
        ]);
    }
}

