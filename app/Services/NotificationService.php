<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use PDO;
use Throwable;

class NotificationService
{
    /**
     * Retrieve active farm operational alerts for in-system notifications and mobile pop-up.
     *
     * @return array<array{id: string, type: string, title: string, message: string, link: string, time: string, is_urgent: bool}>
     */
    public static function getActiveAlerts(): array
    {
        $alerts = [];
        try {
            $pdo = DB::pdo();

            // 1. Hatch today or countdown <= 2 days
            $hatchStmt = $pdo->query("
                SELECT j.id, j.expected_hatch_date, j.eggs_incubated, c.name AS customer_name,
                       DATEDIFF(j.expected_hatch_date, CURDATE()) AS days_left
                FROM incubation_jobs j
                JOIN customers c ON c.id = j.customer_id
                WHERE j.status IN ('received', 'incubating')
                  AND j.expected_hatch_date <= DATE_ADD(CURDATE(), INTERVAL 2 DAY)
                ORDER BY j.expected_hatch_date ASC
                LIMIT 4
            ");
            if ($hatchStmt) {
                while ($h = $hatchStmt->fetch(PDO::FETCH_ASSOC)) {
                    $days = (int) $h['days_left'];
                    $urgent = $days <= 0;
                    $alerts[] = [
                        'id' => 'hatch_' . $h['id'],
                        'type' => $urgent ? 'danger' : 'warning',
                        'title' => $urgent ? 'Hatch Day Today!' : "Hatch Due in {$days} Day(s)",
                        'message' => "Job #{$h['id']} ({$h['customer_name']}) — " . number_format((int)$h['eggs_incubated']) . " eggs scheduled.",
                        'link' => url("/incubation-jobs/{$h['id']}"),
                        'time' => $urgent ? 'Today' : date('d M', strtotime((string)$h['expected_hatch_date'])),
                        'is_urgent' => $urgent,
                    ];
                }
            }

            // 2. High mortality spike in last 3 days (>= 10 birds)
            $mortStmt = $pdo->query("
                SELECT f.id, f.batch_code, e.quantity, e.event_date
                FROM flock_events e
                JOIN flocks f ON f.id = e.flock_id
                WHERE e.event_type = 'death'
                  AND e.event_date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
                  AND e.quantity >= 10
                ORDER BY e.quantity DESC
                LIMIT 3
            ");
            if ($mortStmt) {
                while ($m = $mortStmt->fetch(PDO::FETCH_ASSOC)) {
                    $alerts[] = [
                        'id' => 'mort_' . $m['id'] . '_' . $m['event_date'],
                        'type' => 'danger',
                        'title' => 'High Mortality Spike',
                        'message' => "Flock {$m['batch_code']}: {$m['quantity']} deaths recorded on " . date('d-M', strtotime((string)$m['event_date'])),
                        'link' => url("/flocks/{$m['id']}"),
                        'time' => date('d M', strtotime((string)$m['event_date'])),
                        'is_urgent' => true,
                    ];
                }
            }

            // 3. Aged debtors (> 30 days pending, excluding internal Msantha owner)
            $debtStmt = $pdo->query("
                SELECT c.id, c.name,
                       CAST(COALESCE(j.incubation_cost, 0) - COALESCE(j.incubation_paid, 0) AS SIGNED) AS bal
                FROM customers c
                JOIN (
                    SELECT customer_id, SUM(cost) AS incubation_cost, SUM(amount_paid) AS incubation_paid
                    FROM incubation_jobs
                    WHERE created_at <= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY customer_id
                ) j ON j.customer_id = c.id
                WHERE c.is_internal = 0
                  AND LOWER(c.name) NOT LIKE '%msantha%'
                  AND (j.incubation_cost - j.incubation_paid) > 10000
                ORDER BY bal DESC
                LIMIT 3
            ");
            if ($debtStmt) {
                while ($d = $debtStmt->fetch(PDO::FETCH_ASSOC)) {
                    $alerts[] = [
                        'id' => 'debt_' . $d['id'],
                        'type' => 'warning',
                        'title' => 'Aged Debtor Pending (>30d)',
                        'message' => "{$d['name']} has an outstanding balance of " . number_format((int)$d['bal']) . " MWK.",
                        'link' => url("/customers/{$d['id']}"),
                        'time' => 'Overdue',
                        'is_urgent' => false,
                    ];
                }
            }
        } catch (Throwable) {
            // graceful fallback
        }

        return $alerts;
    }
}

