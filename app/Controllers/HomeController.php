<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use PDO;
use Throwable;

class HomeController
{
    public function index(Request $request): Response
    {
        // When unauthenticated, show the public MIGS landing page
        if (!\App\Core\Auth::check()) {
            return $this->landing($request);
        }

        $pdo = DB::pdo();
        $today = date('Y-m-d');
        $currentMonth = date('Y-m');

        $stats = [
            'total_live_stock' => 0,
            'active_flocks' => 0,
            'deaths_this_week' => 0,
            'eggs_this_week' => 0,
            'egg_trays_this_week' => 0,
            'total_eggs_collected' => 0,
            'incubating_batches' => 0,
            'unpaid_balances_total' => 0,
            'debtors_count' => 0,
        ];

        $stockByBreed = [];
        $incubatingBatches = [];
        $cashPerBusiness = [];
        $alerts = [];

        try {
            // 1. Live stock by breed
            $stockByBreed = $pdo->query("
                SELECT b.name AS breed_name, COALESCE(SUM(v.current_stock), 0) AS stock
                FROM v_flock_status v
                JOIN breeds b ON b.id = v.breed_id
                WHERE v.current_stock > 0
                GROUP BY b.id
                ORDER BY stock DESC
            ")->fetchAll(PDO::FETCH_ASSOC);

            $stats['total_live_stock'] = array_sum(array_column($stockByBreed, 'stock'));
            $stats['active_flocks'] = (int) $pdo->query("SELECT COUNT(*) FROM flocks WHERE status = 'active'")->fetchColumn();

            // 2. Deaths this week (last 7 days)
            $deathsRow = $pdo->query("
                SELECT COALESCE(SUM(quantity), 0) FROM flock_events 
                WHERE event_type = 'death' AND event_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ")->fetchColumn();
            $stats['deaths_this_week'] = (int) $deathsRow;

            // 3. Eggs this week
            $eggsWeekRow = $pdo->query("
                SELECT COALESCE(SUM(collected), 0) FROM egg_production_daily
                WHERE prod_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ")->fetchColumn();
            $stats['eggs_this_week'] = (int) $eggsWeekRow;
            $stats['egg_trays_this_week'] = (int) floor($stats['eggs_this_week'] / 30);
            $stats['total_eggs_collected'] = (int) $pdo->query("SELECT COALESCE(SUM(collected), 0) FROM egg_production_daily")->fetchColumn();

            // 4. Incubating batches + countdown
            $incubatingBatches = $pdo->query("
                SELECT j.id, j.batch_code, j.eggs_incubated, j.expected_hatch_date, j.date_transfer,
                       b.name AS breed_name, c.name AS customer_name,
                       DATEDIFF(j.expected_hatch_date, CURDATE()) AS days_to_hatch
                FROM incubation_jobs j
                LEFT JOIN breeds b ON b.id = j.breed_id
                LEFT JOIN customers c ON c.id = j.customer_id
                WHERE j.status IN ('received', 'incubating')
                ORDER BY j.expected_hatch_date ASC
                LIMIT 6
            ")->fetchAll(PDO::FETCH_ASSOC);
            $stats['incubating_batches'] = (int) $pdo->query("SELECT COUNT(*) FROM incubation_jobs WHERE status IN ('received', 'incubating')")->fetchColumn();

            // 5. Cash today and this month per business
            $cashPerBusiness = $pdo->query("
                SELECT 
                    bu.id, bu.code, bu.name,
                    COALESCE(SUM(CASE WHEN ce.entry_date = '{$today}' AND ce.entry_kind = 'transaction' THEN ce.receipt - ce.expenditure ELSE 0 END), 0) AS net_today,
                    COALESCE(SUM(CASE WHEN DATE_FORMAT(ce.entry_date, '%Y-%m') = '{$currentMonth}' AND ce.entry_kind = 'transaction' THEN ce.receipt - ce.expenditure ELSE 0 END), 0) AS net_month
                FROM business_units bu
                LEFT JOIN cash_entries ce ON ce.business_unit_id = bu.id
                GROUP BY bu.id
                ORDER BY bu.id ASC
            ")->fetchAll(PDO::FETCH_ASSOC);

            // 6. Unpaid balances total
            $debtRow = $pdo->query("
                SELECT 
                    COUNT(DISTINCT j.customer_id) AS debtors,
                    COALESCE(SUM(CAST(j.cost AS SIGNED) - CAST(COALESCE(p.paid, 0) AS SIGNED)), 0) AS total_debt
                FROM incubation_jobs j
                JOIN customers c ON c.id = j.customer_id AND c.is_internal = 0
                LEFT JOIN (
                    SELECT job_id, SUM(amount) AS paid FROM incubation_payments GROUP BY job_id
                ) p ON p.job_id = j.id
                WHERE j.cost > COALESCE(p.paid, 0)
            ")->fetch(PDO::FETCH_ASSOC);

            if ($debtRow) {
                $stats['unpaid_balances_total'] = (int) $debtRow['total_debt'];
                $stats['debtors_count'] = (int) $debtRow['debtors'];
            }

            // 7. Dynamic Alerts
            // Transfer today
            $transferCount = (int) $pdo->query("SELECT COUNT(*) FROM incubation_jobs WHERE date_transfer = '{$today}' AND status IN ('received', 'incubating')")->fetchColumn();
            if ($transferCount > 0) {
                $alerts[] = [
                    'type' => 'info',
                    'title' => 'Transfer to Hatcher Today',
                    'message' => "{$transferCount} batch(es) reached transfer day (candling & tray relocation).",
                    'link' => url('/incubation-jobs'),
                ];
            }

            // Hatching soon (next 3 days)
            $hatchSoonCount = (int) $pdo->query("SELECT COUNT(*) FROM incubation_jobs WHERE expected_hatch_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY) AND status = 'incubating'")->fetchColumn();
            if ($hatchSoonCount > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Hatching Soon (Within 3 Days)',
                    'message' => "{$hatchSoonCount} batch(es) scheduled to complete hatch within the next 72 hours.",
                    'link' => url('/incubation-jobs'),
                ];
            }

            // High mortality check
            $highMort = $pdo->query("
                SELECT f.batch_code, e.quantity, e.event_date
                FROM flock_events e
                JOIN flocks f ON f.id = e.flock_id
                WHERE e.event_type = 'death' AND e.event_date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY) AND e.quantity >= 15
                LIMIT 2
            ")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($highMort as $hm) {
                $alerts[] = [
                    'type' => 'danger',
                    'title' => 'Mortality Notice',
                    'message' => "Flock {$hm['batch_code']} recorded {$hm['quantity']} deaths on " . date('d-M', strtotime($hm['event_date'])) . ".",
                    'link' => url('/flocks'),
                ];
            }

        } catch (Throwable) {
            // Graceful fallback for empty DBs
        }

        return View::render('home/index', [
            'pageTitle' => 'Operational Dashboard',
            'activeNav' => 'home',
            'stats' => $stats,
            'stockByBreed' => $stockByBreed,
            'incubatingBatches' => $incubatingBatches,
            'cashPerBusiness' => $cashPerBusiness,
            'alerts' => $alerts,
        ]);
    }

    /**
     * Public landing page.
     * Route: GET /landing
     */
    public function landing(Request $request): Response
    {
        return View::render('home/landing', [
            'pageTitle' => 'Msantha Investments and General Suppliers (MIGS) — Malawi',
        ], null);
    }

    /**
     * Mobile more operations menu.
     * Route: GET /more
     */
    public function more(Request $request): Response
    {
        \App\Core\Auth::requireLogin();
        return View::render('home/more', [
            'pageTitle' => 'All Operations',
            'activeNav' => 'more',
        ]);
    }
}
