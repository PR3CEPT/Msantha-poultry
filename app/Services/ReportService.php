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
        foreach ($rows as $r) {
            $totalReceipts += (int) $r['total_receipts'];
            $totalExpenses += (int) $r['total_expenses'];
        }

        return [
            'rows' => $rows,
            'total_receipts' => $totalReceipts,
            'total_expenses' => $totalExpenses,
            'net_profit' => $totalReceipts - $totalExpenses,
        ];
    }

    /**
     * 2. Mortality % per flock report.
     * @return array<int, array<string, mixed>>
     */
    public function getMortalityPerFlock(?int $flockId = null): array
    {
        $where = " WHERE 1=1";
        $params = [];
        if ($flockId !== null) {
            $where .= " AND f.id = ?";
            $params[] = $flockId;
        }

        $sql = "
            SELECT 
                f.id,
                f.batch_code,
                b.name AS breed_name,
                f.start_date,
                f.start_count,
                f.status,
                COALESCE((SELECT SUM(quantity) FROM flock_events WHERE flock_id = f.id AND event_type = 'death'), 0) AS total_deaths,
                COALESCE((SELECT SUM(quantity) FROM flock_events WHERE flock_id = f.id AND event_type = 'sale'), 0) AS total_sold,
                COALESCE((SELECT SUM(quantity) FROM flock_events WHERE flock_id = f.id AND event_type = 'cull'), 0) AS total_culled,
                COALESCE((SELECT SUM(quantity) FROM flock_events WHERE flock_id = f.id AND event_type = 'addition'), 0) AS total_additions
            FROM flocks f
            LEFT JOIN breeds b ON b.id = f.breed_id
            {$where}
            ORDER BY f.start_date DESC, f.id DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
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
     * 4. Egg productivity report.
     * @return array<int, array<string, mixed>>
     */
    public function getEggProductivityReport(?string $from = null, ?string $to = null, ?int $breedId = null): array
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

        $sql = "
            SELECT 
                DATE_FORMAT(ep.prod_date, '%Y-%m') AS month_key,
                b.name AS breed_name,
                SUM(ep.collected) AS total_collected,
                SUM(ep.broken) AS total_broken,
                SUM(ep.consumed) AS total_consumed,
                SUM(ep.sold) AS total_sold,
                SUM(ep.incubated) AS total_incubated
            FROM egg_production_daily ep
            LEFT JOIN breeds b ON b.id = ep.breed_id
            {$where}
            GROUP BY month_key, ep.breed_id
            ORDER BY month_key DESC, ep.breed_id ASC
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
                'month' => (string) $r['month_key'],
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
            WHERE c.is_internal = 0
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
}

