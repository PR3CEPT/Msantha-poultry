<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use PDO;

class ReportService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? DB::pdo();
    }

    /**
     * 1. Monthly Profit & Loss Report across all business units.
     * @return array<string, mixed>
     */
    public function getMonthlyPnl(?int $year = null): array
    {
        $where = " WHERE ce.entry_kind = 'transaction'";
        $params = [];
        if ($year !== null) {
            $where .= " AND (ce.entry_date IS NULL OR YEAR(ce.entry_date) = ?)";
            $params[] = $year;
        }

        $sql = "
            SELECT 
                COALESCE(DATE_FORMAT(ce.entry_date, '%Y-%m'), 'Pre-dated') AS month_key,
                bu.code AS unit_code,
                bu.name AS unit_name,
                SUM(ce.receipt) AS total_receipts,
                SUM(ce.expenditure) AS total_expenses,
                (SUM(ce.receipt) - SUM(ce.expenditure)) AS net_profit
            FROM cash_entries ce
            JOIN business_units bu ON bu.id = ce.business_unit_id
            {$where}
            GROUP BY month_key, bu.id
            ORDER BY month_key DESC, bu.id ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalReceipts = 0;
        $totalExpenses = 0;
        $poultryReceipts = 0;
        $poultryExpenses = 0;
        $poultryProfit = 0;
        $byUnit = [];

        foreach ($rows as $r) {
            $rec = (int) $r['total_receipts'];
            $exp = (int) $r['total_expenses'];
            $net = (int) $r['net_profit'];
            $totalReceipts += $rec;
            $totalExpenses += $exp;

            $code = (string) $r['unit_code'];
            if (!isset($byUnit[$code])) {
                $byUnit[$code] = [
                    'code' => $code,
                    'name' => (string) $r['unit_name'],
                    'receipts' => 0,
                    'expenses' => 0,
                    'net' => 0,
                ];
            }
            $byUnit[$code]['receipts'] += $rec;
            $byUnit[$code]['expenses'] += $exp;
            $byUnit[$code]['net'] += $net;

            if ($code === 'POULTRY') {
                $poultryReceipts += $rec;
                $poultryExpenses += $exp;
                $poultryProfit += $net;
            }
        }

        return [
            'rows' => $rows,
            'total_receipts' => $totalReceipts,
            'total_expenses' => $totalExpenses,
            'net_profit' => $totalReceipts - $totalExpenses,
            'poultry_receipts' => $poultryReceipts,
            'poultry_expenses' => $poultryExpenses,
            'poultry_profit' => $poultryProfit,
            'by_unit' => $byUnit,
        ];
    }

    /**
     * 2. Mortality % per flock report with customizable date period.
     * @return array<int, array<string, mixed>>
     */
    public function getMortalityPerFlock(?int $flockId = null, ?string $from = null, ?string $to = null): array
    {
        $where = " WHERE 1=1";
        $params = [];
        if ($flockId !== null) {
            $where .= " AND f.id = ?";
            $params[] = $flockId;
        }

        $dateFilter = "";
        $dateParams = [];
        if ($from !== null && $from !== '') {
            $dateFilter .= " AND event_date >= ?";
            $dateParams[] = $from;
        }
        if ($to !== null && $to !== '') {
            $dateFilter .= " AND event_date <= ?";
            $dateParams[] = $to;
        }

        $sql = "
            SELECT 
                f.id,
                f.batch_code,
                b.name AS breed_name,
                f.start_date,
                f.start_count,
                f.status,
                COALESCE((SELECT SUM(quantity) FROM flock_events WHERE flock_id = f.id AND event_type = 'death' {$dateFilter}), 0) AS total_deaths,
                COALESCE((SELECT SUM(quantity) FROM flock_events WHERE flock_id = f.id AND event_type = 'sale' {$dateFilter}), 0) AS total_sold,
                COALESCE((SELECT SUM(quantity) FROM flock_events WHERE flock_id = f.id AND event_type = 'cull' {$dateFilter}), 0) AS total_culled,
                COALESCE((SELECT SUM(quantity) FROM flock_events WHERE flock_id = f.id AND event_type = 'addition' {$dateFilter}), 0) AS total_additions
            FROM flocks f
            LEFT JOIN breeds b ON b.id = f.breed_id
            {$where}
            ORDER BY f.start_date DESC, f.id DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $allParams = array_merge($dateParams, $dateParams, $dateParams, $dateParams, $params);
        $stmt->execute($allParams);
        $flocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($flocks as $f) {
            $start = (int) $f['start_count'];
            $deaths = (int) $f['total_deaths'];
            $sold = (int) $f['total_sold'];
            $culled = (int) $f['total_culled'];
            $additions = (int) $f['total_additions'];

            $currentStock = max(0, $start + $additions - $deaths - $sold - $culled);
            $mortalityRate = $start > 0 ? round(($deaths / $start) * 100, 2) : 0.0;

            $results[] = [
                'flock_id' => (int) $f['id'],
                'batch_code' => $f['batch_code'],
                'breed_name' => $f['breed_name'] ?: 'Mixed / Local',
                'start_date' => $f['start_date'],
                'start_count' => $start,
                'total_deaths' => $deaths,
                'total_sold' => $sold,
                'current_stock' => $currentStock,
                'mortality_rate' => $mortalityRate,
                'is_active' => ($f['status'] === 'active'),
            ];
        }

        return $results;
    }

    /**
     * 3. Hatch % report grouped by breed, customer, or month.
     * @return array<int, array<string, mixed>>
     */
    public function getHatchRateReport(string $groupBy = 'breed', ?string $from = null, ?string $to = null): array
    {
        $groupCol = match ($groupBy) {
            'customer' => "c.name",
            'month' => "COALESCE(DATE_FORMAT(j.date_received, '%Y-%m'), 'Unknown')",
            default => "COALESCE(b.name, 'Local/Mixed')",
        };

        $where = " WHERE j.eggs_incubated > 0";
        $params = [];
        if ($from !== null && $from !== '') {
            $where .= " AND j.date_received >= ?";
            $params[] = $from;
        }
        if ($to !== null && $to !== '') {
            $where .= " AND j.date_received <= ?";
            $params[] = $to;
        }

        $sql = "
            SELECT 
                {$groupCol} AS group_label,
                COUNT(j.id) AS total_batches,
                SUM(j.eggs_incubated) AS total_incubated,
                SUM(COALESCE(j.infertile, 0)) AS total_infertile,
                SUM(COALESCE(j.hatched, 0)) AS total_hatched
            FROM incubation_jobs j
            LEFT JOIN breeds b ON b.id = j.breed_id
            LEFT JOIN customers c ON c.id = j.customer_id
            {$where}
            GROUP BY group_label
            ORDER BY total_incubated DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $r) {
            $inc = (int) $r['total_incubated'];
            $hatched = (int) $r['total_hatched'];
            $rate = $inc > 0 ? round(($hatched / $inc) * 100, 2) : 0.0;

            $results[] = [
                'label' => (string) $r['group_label'],
                'total_batches' => (int) $r['total_batches'],
                'total_incubated' => $inc,
                'total_infertile' => (int) $r['total_infertile'],
                'total_hatched' => $hatched,
                'hatch_rate' => $rate,
            ];
        }

        return $results;
    }

    /**
     * 4. Egg productivity report with customizable daily/weekly/monthly grouping.
     * @return array<int, array<string, mixed>>
     */
    public function getEggProductivityReport(?string $from = null, ?string $to = null, ?int $breedId = null, string $groupFormat = 'auto'): array
    {
        $where = " WHERE 1=1";
        $params = [];
        if ($from !== null && $from !== '') {
            $where .= " AND ep.prod_date >= ?";
            $params[] = $from;
        }
        if ($to !== null && $to !== '') {
            $where .= " AND ep.prod_date <= ?";
            $params[] = $to;
        }
        if ($breedId !== null) {
            $where .= " AND ep.breed_id = ?";
            $params[] = $breedId;
        }

        $isDaily = ($groupFormat === 'daily') || ($groupFormat === 'auto' && $from && $to && (strtotime($to) - strtotime($from) <= 14 * 86400));
        $dateCol = $isDaily ? "DATE_FORMAT(ep.prod_date, '%d-%b-%Y')" : "DATE_FORMAT(ep.prod_date, '%Y-%m')";

        $sql = "
            SELECT 
                {$dateCol} AS period_key,
                b.name AS breed_name,
                SUM(ep.collected) AS total_collected,
                SUM(ep.broken) AS total_broken,
                SUM(ep.consumed) AS total_consumed,
                SUM(ep.sold) AS total_sold,
                SUM(ep.incubated) AS total_incubated
            FROM egg_production_daily ep
            LEFT JOIN breeds b ON b.id = ep.breed_id
            {$where}
            GROUP BY period_key, ep.breed_id
            ORDER BY ep.prod_date DESC, ep.breed_id ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $r) {
            $col = (int) $r['total_collected'];
            $trays = floor($col / 30.0);
            $loose = $col % 30;

            $results[] = [
                'month' => (string) $r['period_key'],
                'breed' => (string) ($r['breed_name'] ?: 'Layers / General'),
                'collected' => $col,
                'broken' => (int) $r['total_broken'],
                'consumed' => (int) $r['total_consumed'],
                'sold' => (int) $r['total_sold'],
                'incubated' => (int) $r['total_incubated'],
                'tray_equivalents' => (int) $trays,
                'loose_eggs' => (int) $loose,
            ];
        }

        return $results;
    }

    /**
     * 5. Feed Cost Efficiency per Bird & per Egg.
     * @return array<string, mixed>
     */
    public function getFeedCostEfficiency(?int $year = null): array
    {
        // 1. Total feed expenses
        $feedWhere = "";
        $feedParams = [];
        if ($year !== null) {
            $feedWhere = " WHERE YEAR(purchase_date) = ?";
            $feedParams[] = $year;
        }
        $feedStmt = $this->pdo->prepare("SELECT COALESCE(SUM(cost), 0) FROM feed_purchases {$feedWhere}");
        $feedStmt->execute($feedParams);
        $totalFeedCost = (int) $feedStmt->fetchColumn();

        // 2. Total birds stocked (initial intake count across flocks)
        $flockWhere = "";
        $flockParams = [];
        if ($year !== null) {
            $flockWhere = " WHERE YEAR(start_date) = ?";
            $flockParams[] = $year;
        }
        $flockStmt = $this->pdo->prepare("SELECT COALESCE(SUM(start_count), 0) FROM flocks {$flockWhere}");
        $flockStmt->execute($flockParams);
        $totalBirdsStocked = (int) $flockStmt->fetchColumn();

        // 3. Total eggs collected
        $eggWhere = "";
        $eggParams = [];
        if ($year !== null) {
            $eggWhere = " WHERE YEAR(prod_date) = ?";
            $eggParams[] = $year;
        }
        $eggStmt = $this->pdo->prepare("SELECT COALESCE(SUM(collected), 0) FROM egg_production_daily {$eggWhere}");
        $eggStmt->execute($eggParams);
        $totalEggsProduced = (int) $eggStmt->fetchColumn();

        $costPerBird = $totalBirdsStocked > 0 ? (int) round($totalFeedCost / $totalBirdsStocked) : 0;
        $costPerEgg = $totalEggsProduced > 0 ? (int) round($totalFeedCost / $totalEggsProduced) : 0;

        return [
            'year' => $year,
            'total_feed_cost' => $totalFeedCost,
            'total_birds_stocked' => $totalBirdsStocked,
            'total_eggs_produced' => $totalEggsProduced,
            'feed_cost_per_bird' => $costPerBird,
            'feed_cost_per_egg' => $costPerEgg,
        ];
    }

    /**
     * 6. Aged Debtors Report (outstanding customer balances categorized into 0-30, 31-60, 61-90, 90+ days).
     * @return array<int, array<string, mixed>>
     */
    public function getAgedDebtors(): array
    {
        $sql = "
            SELECT 
                c.id AS customer_id,
                c.name AS customer_name,
                c.phone AS customer_phone,
                j.id AS job_id,
                j.date_hatched,
                j.cost,
                COALESCE((SELECT SUM(amount) FROM incubation_payments WHERE job_id = j.id), 0) AS amount_paid,
                DATEDIFF(CURDATE(), COALESCE(j.date_hatched, j.date_received, CURDATE())) AS days_aged
            FROM incubation_jobs j
            JOIN customers c ON c.id = j.customer_id
            WHERE c.is_internal = 0 AND LOWER(c.name) NOT LIKE '%msantha%'
            HAVING (CAST(j.cost AS SIGNED) - CAST(amount_paid AS SIGNED)) > 0
            ORDER BY days_aged DESC, (CAST(j.cost AS SIGNED) - CAST(amount_paid AS SIGNED)) DESC
        ";
        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $customerMap = [];
        foreach ($rows as $r) {
            $cId = (int) $r['customer_id'];
            $debt = (int) ($r['cost'] - $r['amount_paid']);
            $days = max(0, (int) $r['days_aged']);

            if (!isset($customerMap[$cId])) {
                $customerMap[$cId] = [
                    'customer_id' => $cId,
                    'name' => $r['customer_name'],
                    'phone' => $r['customer_phone'] ?: '—',
                    'current_30' => 0,
                    'aged_31_60' => 0,
                    'aged_61_90' => 0,
                    'aged_over_90' => 0,
                    'total_due' => 0,
                ];
            }

            if ($days <= 30) {
                $customerMap[$cId]['current_30'] += $debt;
            } elseif ($days <= 60) {
                $customerMap[$cId]['aged_31_60'] += $debt;
            } elseif ($days <= 90) {
                $customerMap[$cId]['aged_61_90'] += $debt;
            } else {
                $customerMap[$cId]['aged_over_90'] += $debt;
            }

            $customerMap[$cId]['total_due'] += $debt;
        }

        return array_values($customerMap);
    }

    /**
     * 7. Commercial Sales by Item Type report.
     * @return array<int, array<string, mixed>>
     */
    public function getSalesByItem(?string $from = null, ?string $to = null): array
    {
        $where = " WHERE 1=1";
        $params = [];
        if ($from !== null && $from !== '') {
            $where .= " AND s.sale_date >= ?";
            $params[] = $from;
        }
        if ($to !== null && $to !== '') {
            $where .= " AND s.sale_date <= ?";
            $params[] = $to;
        }

        $sql = "
            SELECT 
                si.item_type,
                COUNT(DISTINCT s.id) AS total_orders,
                SUM(si.quantity) AS total_quantity,
                SUM(si.amount) AS total_revenue
            FROM sale_items si
            JOIN sales s ON s.id = si.sale_id
            {$where}
            GROUP BY si.item_type
            ORDER BY total_revenue DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $r) {
            $results[] = [
                'item_type' => (string) $r['item_type'],
                'label' => ucwords(str_replace('_', ' ', (string) $r['item_type'])),
                'orders_count' => (int) $r['total_orders'],
                'total_quantity' => (int) $r['total_quantity'],
                'total_revenue' => (int) $r['total_revenue'],
            ];
        }

        return $results;
    }

    /**
     * 8. Incubation Income by Allocation Category report.
     * @return array<string, mixed>
     */
    public function getIncubationAllocationIncome(?int $year = null): array
    {
        $where = "";
        $params = [];
        if ($year !== null) {
            $where = " WHERE YEAR(ip.paid_on) = ?";
            $params[] = $year;
        }

        $sql = "
            SELECT 
                DATE_FORMAT(ip.paid_on, '%Y-%m') AS month_key,
                SUM(CASE WHEN ia.category = 'electricity' THEN ia.amount ELSE 0 END) AS electricity,
                SUM(CASE WHEN ia.category = 'water' THEN ia.amount ELSE 0 END) AS water,
                SUM(CASE WHEN ia.category = 'labour' THEN ia.amount ELSE 0 END) AS labour,
                SUM(CASE WHEN ia.category = 'telephone' THEN ia.amount ELSE 0 END) AS telephone,
                SUM(CASE WHEN ia.category = 'profit' THEN ia.amount ELSE 0 END) AS profit,
                SUM(ia.amount) AS total_month
            FROM incubation_allocations ia
            JOIN incubation_payments ip ON ip.id = ia.payment_id
            {$where}
            GROUP BY month_key
            ORDER BY month_key DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totals = [
            'electricity' => 0,
            'water' => 0,
            'labour' => 0,
            'telephone' => 0,
            'profit' => 0,
            'grand_total' => 0,
        ];

        foreach ($rows as $r) {
            $totals['electricity'] += (int) $r['electricity'];
            $totals['water'] += (int) $r['water'];
            $totals['labour'] += (int) $r['labour'];
            $totals['telephone'] += (int) $r['telephone'];
            $totals['profit'] += (int) $r['profit'];
            $totals['grand_total'] += (int) $r['total_month'];
        }

        return [
            'monthly_rows' => $rows,
            'totals' => $totals,
        ];
    }

    /**
     * 9. Executive Business Intelligence & Performance Report:
     * - Best selling products (revenue & volume)
     * - Worst selling / slow-moving products
     * - High consumers (top customer accounts)
     * - Profits & losses breakdown
     * - Flock mortality financial loss (in MWK)
     * - Feed efficiency & high consumers
     * - Management decision-making action items
     */
    public function getExecutivePerformanceReport(?string $from = null, ?string $to = null, ?int $year = null): array
    {
        // 1. Establish date bounds
        if ($year !== null && empty($from) && empty($to)) {
            $from = "{$year}-01-01";
            $to = "{$year}-12-31";
        }
        $dateFilterDesc = ($from && $to) ? "{$from} to {$to}" : ($year ? "Year {$year}" : "All Time to Date");

        // 2. Sales by Product Line (Best Sellers vs Worst Sellers)
        $saleWhere = " WHERE 1=1";
        $saleParams = [];
        if (!empty($from)) {
            $saleWhere .= " AND s.sale_date >= ?";
            $saleParams[] = $from;
        }
        if (!empty($to)) {
            $saleWhere .= " AND s.sale_date <= ?";
            $saleParams[] = $to;
        }

        // Query sale_items
        $productQuery = "
            SELECT 
                si.item_type,
                COUNT(DISTINCT s.id) AS orders_count,
                COALESCE(SUM(si.quantity), 0) AS total_units,
                COALESCE(SUM(si.amount), 0) AS total_revenue
            FROM sale_items si
            JOIN sales s ON s.id = si.sale_id
            {$saleWhere}
            GROUP BY si.item_type
            ORDER BY total_revenue DESC
        ";
        $pStmt = $this->pdo->prepare($productQuery);
        $pStmt->execute($saleParams);
        $existingSales = $pStmt->fetchAll(PDO::FETCH_ASSOC);

        // Also incorporate Incubation Service as a primary product/revenue line
        $incWhere = " WHERE 1=1";
        $incParams = [];
        if (!empty($from)) {
            $incWhere .= " AND j.date_received >= ?";
            $incParams[] = $from;
        }
        if (!empty($to)) {
            $incWhere .= " AND j.date_received <= ?";
            $incParams[] = $to;
        }
        $incStmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) AS batches_count,
                COALESCE(SUM(j.eggs_incubated), 0) AS total_eggs,
                COALESCE(SUM(j.cost), 0) AS total_invoiced,
                COALESCE(SUM(j.amount_paid), 0) AS total_paid
            FROM incubation_jobs j
            {$incWhere}
        ");
        $incStmt->execute($incParams);
        $incSummary = $incStmt->fetch(PDO::FETCH_ASSOC) ?: ['batches_count' => 0, 'total_eggs' => 0, 'total_invoiced' => 0, 'total_paid' => 0];

        // All standard poultry product lines catalog
        $allProductLines = [
            'table_eggs' => 'Table / Fresh Eating Eggs',
            'fertilised_eggs' => 'Fertilised Hatching Eggs',
            'chicks' => 'Day-Old & Started Chicks',
            'live_birds' => 'Live Mature Birds (Broilers/Layers)',
            'incubation_services' => 'Custom Incubation Service',
            'manure' => 'Organic Poultry Manure',
            'other' => 'Feed & Vet Equipment',
        ];

        $productPerformance = [];
        $totalGrossRevenue = (int) $incSummary['total_invoiced'];

        // Add incubation services
        $productPerformance['incubation_services'] = [
            'key' => 'incubation_services',
            'label' => 'Custom Incubation Service',
            'orders_count' => (int) $incSummary['batches_count'],
            'total_units' => (int) $incSummary['total_eggs'],
            'unit_name' => 'eggs',
            'total_revenue' => (int) $incSummary['total_invoiced'],
            'avg_price' => $incSummary['total_eggs'] > 0 ? (int) round($incSummary['total_invoiced'] / $incSummary['total_eggs']) : 120,
        ];

        foreach ($existingSales as $es) {
            $k = (string) $es['item_type'];
            $rev = (int) $es['total_revenue'];
            $qty = (int) $es['total_units'];
            $totalGrossRevenue += $rev;

            $productPerformance[$k] = [
                'key' => $k,
                'label' => $allProductLines[$k] ?? ucwords(str_replace('_', ' ', $k)),
                'orders_count' => (int) $es['orders_count'],
                'total_units' => $qty,
                'unit_name' => in_array($k, ['table_eggs', 'fertilised_eggs']) ? 'trays/eggs' : (in_array($k, ['chicks', 'live_birds']) ? 'birds' : 'units'),
                'total_revenue' => $rev,
                'avg_price' => $qty > 0 ? (int) round($rev / $qty) : 0,
            ];
        }

        // Fill remaining zero-movement lines
        foreach ($allProductLines as $k => $label) {
            if (!isset($productPerformance[$k])) {
                $productPerformance[$k] = [
                    'key' => $k,
                    'label' => $label,
                    'orders_count' => 0,
                    'total_units' => 0,
                    'unit_name' => 'units',
                    'total_revenue' => 0,
                    'avg_price' => 0,
                ];
            }
        }

        // Calculate % share of total revenue
        foreach ($productPerformance as &$p) {
            $p['share_pct'] = $totalGrossRevenue > 0 ? round(($p['total_revenue'] / $totalGrossRevenue) * 100, 1) : 0.0;
        }
        unset($p);

        // Sort descending by revenue
        uasort($productPerformance, fn($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);
        $productPerformanceList = array_values($productPerformance);

        $bestSellers = array_values(array_filter($productPerformanceList, fn($p) => $p['total_revenue'] > 0));
        $worstSellers = array_values(array_filter($productPerformanceList, fn($p) => $p['total_revenue'] === 0 || $p['orders_count'] <= 1));

        // 3. High Consumers / Top 10 Customers (Spend across POS + Incubation)
        $topCustomerQuery = "
            SELECT 
                c.id, c.name, c.phone, c.location,
                COALESCE(s.tot_sales, 0) AS sales_spend,
                COALESCE(s.sales_orders, 0) AS sales_orders,
                COALESCE(j.tot_inc, 0) AS inc_spend,
                COALESCE(j.inc_batches, 0) AS inc_batches,
                COALESCE(j.tot_paid, 0) AS inc_paid,
                (COALESCE(s.tot_sales, 0) + COALESCE(j.tot_inc, 0)) AS total_spend,
                GREATEST(0, CAST(COALESCE(j.tot_inc, 0) AS SIGNED) - CAST(COALESCE(j.tot_paid, 0) AS SIGNED)) AS balance_due
            FROM customers c
            LEFT JOIN (
                SELECT customer_id, SUM(total) AS tot_sales, COUNT(*) AS sales_orders 
                FROM sales 
                " . (!empty($from) || !empty($to) ? "WHERE 1=1 " . (!empty($from) ? "AND sale_date >= '{$from}' " : "") . (!empty($to) ? "AND sale_date <= '{$to}' " : "") : "") . "
                GROUP BY customer_id
            ) s ON s.customer_id = c.id
            LEFT JOIN (
                SELECT customer_id, SUM(cost) AS tot_inc, SUM(amount_paid) AS tot_paid, COUNT(*) AS inc_batches 
                FROM incubation_jobs 
                " . (!empty($from) || !empty($to) ? "WHERE 1=1 " . (!empty($from) ? "AND date_received >= '{$from}' " : "") . (!empty($to) ? "AND date_received <= '{$to}' " : "") : "") . "
                GROUP BY customer_id
            ) j ON j.customer_id = c.id
            WHERE c.is_internal = 0 AND LOWER(c.name) NOT LIKE '%msantha%'
            ORDER BY total_spend DESC
            LIMIT 10
        ";
        $topCustomers = $this->pdo->query($topCustomerQuery)->fetchAll(PDO::FETCH_ASSOC);

        // 4. Profits & Losses Analysis
        // A. Inflows: Cash entries (Poultry) + Incubation fees
        $cashWhere = " WHERE bu.code = 'POULTRY' AND ce.entry_kind = 'transaction'";
        $cashParams = [];
        if (!empty($from)) {
            $cashWhere .= " AND ce.entry_date >= ?";
            $cashParams[] = $from;
        }
        if (!empty($to)) {
            $cashWhere .= " AND ce.entry_date <= ?";
            $cashParams[] = $to;
        }
        $cashStmt = $this->pdo->prepare("
            SELECT 
                COALESCE(SUM(ce.receipt), 0) AS total_cash_receipts,
                COALESCE(SUM(ce.expenditure), 0) AS total_cash_expenses
            FROM cash_entries ce
            JOIN business_units bu ON bu.id = ce.business_unit_id
            {$cashWhere}
        ");
        $cashStmt->execute($cashParams);
        $cashTotals = $cashStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_cash_receipts' => 0, 'total_cash_expenses' => 0];

        $cashReceipts = (int) $cashTotals['total_cash_receipts'];
        $cashExpenses = (int) $cashTotals['total_cash_expenses'];

        // Feed purchases total in period
        $feedWhere = " WHERE 1=1";
        $feedParams = [];
        if (!empty($from)) {
            $feedWhere .= " AND purchase_date >= ?";
            $feedParams[] = $from;
        }
        if (!empty($to)) {
            $feedWhere .= " AND purchase_date <= ?";
            $feedParams[] = $to;
        }
        $feedStmt = $this->pdo->prepare("SELECT COALESCE(SUM(cost), 0) FROM feed_purchases {$feedWhere}");
        $feedStmt->execute($feedParams);
        $feedCost = (int) $feedStmt->fetchColumn();

        // Total gross revenue: use cash receipts + incubation total paid
        $grossInflow = max($totalGrossRevenue, $cashReceipts);

        // B. Flock Mortality Loss (Calculation of Financial Loss in MWK!)
        // Standard replacement value per bird in Malawi: MWK 4,500
        $deathWhere = " WHERE event_type = 'death'";
        $deathParams = [];
        if (!empty($from)) {
            $deathWhere .= " AND event_date >= ?";
            $deathParams[] = $from;
        }
        if (!empty($to)) {
            $deathWhere .= " AND event_date <= ?";
            $deathParams[] = $to;
        }
        $deathStmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) AS death_events,
                COALESCE(SUM(quantity), 0) AS birds_lost,
                COALESCE(SUM(amount), 0) AS recorded_loss_amount
            FROM flock_events
            {$deathWhere}
        ");
        $deathStmt->execute($deathParams);
        $deathRow = $deathStmt->fetch(PDO::FETCH_ASSOC) ?: ['death_events' => 0, 'birds_lost' => 0, 'recorded_loss_amount' => 0];

        $birdsLost = (int) $deathRow['birds_lost'];
        $mortalityLossMwk = (int) $deathRow['recorded_loss_amount'];
        if ($mortalityLossMwk <= 0 && $birdsLost > 0) {
            $mortalityLossMwk = $birdsLost * 4500; // estimated standard value MWK 4,500 / bird
        }

        // Net Operating Profit
        $totalOperatingCosts = $cashExpenses > 0 ? $cashExpenses : $feedCost;
        $netProfit = $grossInflow - $totalOperatingCosts;
        $profitMarginPct = $grossInflow > 0 ? round(($netProfit / $grossInflow) * 100, 1) : 0.0;

        // 5. High Mortality Flocks
        $highConsumersFlocks = $this->pdo->query("
            SELECT f.id, f.batch_code, b.name as breed_name, f.start_count,
                   COALESCE(SUM(fe.quantity), 0) as total_deaths
            FROM flocks f
            JOIN breeds b ON b.id = f.breed_id
            LEFT JOIN flock_events fe ON fe.flock_id = f.id AND fe.event_type = 'death'
            GROUP BY f.id
            ORDER BY total_deaths DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        // 6. Actionable Decision Making Insights
        $insights = [];
        if (!empty($bestSellers)) {
            $topP = $bestSellers[0];
            $insights[] = [
                'type' => 'success',
                'title' => 'Core Revenue Engine',
                'text' => "{$topP['label']} is your #1 revenue generator, accounting for MWK " . number_format($topP['total_revenue']) . " ({$topP['share_pct']}% of total revenue). Maintain strong capacity and inventory.",
            ];
        }

        if ($birdsLost > 0) {
            $insights[] = [
                'type' => 'danger',
                'title' => 'Mortality Loss Impact',
                'text' => "A total of " . number_format($birdsLost) . " birds were lost in this period, representing an economic loss of MWK " . number_format($mortalityLossMwk) . ". Immediate biosecurity and vaccination reviews are advised.",
            ];
        }

        if (!empty($worstSellers)) {
            $slowLabels = implode(', ', array_slice(array_column($worstSellers, 'label'), 0, 3));
            $insights[] = [
                'type' => 'warning',
                'title' => 'Slow-Moving Lines',
                'text' => "{$slowLabels} had minimal or zero activity. Review pricing, package bundling, or customer awareness campaigns.",
            ];
        }

        return [
            'period_desc' => $dateFilterDesc,
            'from' => $from,
            'to' => $to,
            'year' => $year,
            'best_sellers' => $bestSellers,
            'worst_sellers' => $worstSellers,
            'all_products' => $productPerformanceList,
            'top_customers' => $topCustomers,
            'financials' => [
                'gross_revenue' => $grossInflow,
                'operating_expenses' => $totalOperatingCosts,
                'feed_cost' => $feedCost,
                'mortality_birds_lost' => $birdsLost,
                'mortality_loss_mwk' => $mortalityLossMwk,
                'net_profit' => $netProfit,
                'profit_margin_pct' => $profitMarginPct,
            ],
            'high_consumers_flocks' => $highConsumersFlocks,
            'insights' => $insights,
        ];
    }
}

