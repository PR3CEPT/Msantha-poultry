<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\DB;
use PDO;
use Throwable;

class NotificationService
{
    /**
     * Retrieve all active reminders and notifications for the current authenticated user.
     * Returns categorized alerts with unread and seen trail states.
     *
     * @return array{
     *     alerts: array<array{id: string, key: string, category: string, type: string, title: string, message: string, link: string, time: string, is_seen: bool, is_urgent: bool}>,
     *     unread_count: int,
     *     trail_count: int
     * }
     */
    public static function getUserNotifications(?int $userId = null): array
    {
        if ($userId === null) {
            $user = Auth::user();
            $userId = $user ? (int)$user['id'] : 0;
        }

        $allRawAlerts = self::collectAllReminders();

        if ($userId <= 0) {
            return [
                'alerts' => $allRawAlerts,
                'unread_count' => count($allRawAlerts),
                'trail_count' => 0,
            ];
        }

        // Query user_notification_status
        $userStatus = [];
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare("SELECT notification_key, is_seen, is_deleted FROM user_notification_status WHERE user_id = ?");
            $stmt->execute([$userId]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $userStatus[$row['notification_key']] = [
                    'is_seen' => (bool)$row['is_seen'],
                    'is_deleted' => (bool)$row['is_deleted'],
                ];
            }
        } catch (Throwable) {
            // fallback gracefully
        }

        $finalAlerts = [];
        $unreadCount = 0;
        $trailCount = 0;

        foreach ($allRawAlerts as $alert) {
            $key = $alert['key'];
            if (isset($userStatus[$key]) && $userStatus[$key]['is_deleted']) {
                // Permanently deleted by user: does not appear in trail or active list
                continue;
            }

            $isSeen = isset($userStatus[$key]) && $userStatus[$key]['is_seen'];
            $alert['is_seen'] = $isSeen;

            if (!$isSeen) {
                $unreadCount++;
            } else {
                $trailCount++;
            }

            $finalAlerts[] = $alert;
        }

        return [
            'alerts' => $finalAlerts,
            'unread_count' => $unreadCount,
            'trail_count' => $trailCount,
        ];
    }

    /**
     * Backward-compatible helper that returns only unread active alerts.
     */
    public static function getActiveAlerts(?int $userId = null): array
    {
        $res = self::getUserNotifications($userId);
        return array_values(array_filter($res['alerts'], fn($a) => !$a['is_seen']));
    }

    /**
     * Collect all real-time reminders across farm operations, SMS, hatchery, flocks, and finances.
     */
    private static function collectAllReminders(): array
    {
        $alerts = [];
        try {
            $pdo = DB::pdo();

            // 1. SMS Reminders (Failed Deliveries & Pending Queue)
            $failedSmsStmt = $pdo->query("
                SELECT id, to_phone, error, created_at
                FROM sms_outbox
                WHERE status IN ('failed', 'error')
                ORDER BY id DESC
                LIMIT 4
            ");
            if ($failedSmsStmt) {
                while ($sms = $failedSmsStmt->fetch(PDO::FETCH_ASSOC)) {
                    $key = 'sms_failed_' . $sms['id'];
                    $alerts[] = [
                        'id' => $key,
                        'key' => $key,
                        'category' => 'SMS',
                        'type' => 'danger',
                        'title' => 'SMS Delivery Failed',
                        'message' => "Recipient {$sms['to_phone']} failed: " . ($sms['error'] ?: 'Gateway delivery timeout'),
                        'link' => url('/admin/sms'),
                        'time' => date('H:i', strtotime((string)$sms['created_at'])),
                        'is_urgent' => true,
                        'is_seen' => false,
                    ];
                }
            }

            $pendingSmsCount = (int)$pdo->query("SELECT COUNT(*) FROM sms_outbox WHERE status = 'queued'")->fetchColumn();
            if ($pendingSmsCount > 0) {
                $key = 'sms_pending_queue_' . date('YmdH');
                $alerts[] = [
                    'id' => $key,
                    'key' => $key,
                    'category' => 'SMS',
                    'type' => 'info',
                    'title' => "{$pendingSmsCount} Outgoing SMS in Queue",
                    'message' => "Automated reminders awaiting carrier delivery dispatch.",
                    'link' => url('/admin/sms'),
                    'time' => 'Queue',
                    'is_urgent' => false,
                    'is_seen' => false,
                ];
            }

            // 2. Day 18 Candling & Setter-to-Hatcher Transfer Reminder
            $transferStmt = $pdo->query("
                SELECT j.id, j.date_received, j.eggs_incubated, c.name AS customer_name,
                       DATEDIFF(CURDATE(), j.date_received) AS days_elapsed
                FROM incubation_jobs j
                JOIN customers c ON c.id = j.customer_id
                WHERE j.status = 'incubating'
                  AND DATEDIFF(CURDATE(), j.date_received) >= 18
                  AND DATEDIFF(CURDATE(), j.date_received) <= 20
                ORDER BY j.date_received ASC
                LIMIT 4
            ");
            if ($transferStmt) {
                while ($t = $transferStmt->fetch(PDO::FETCH_ASSOC)) {
                    $key = 'transfer_day18_' . $t['id'];
                    $alerts[] = [
                        'id' => $key,
                        'key' => $key,
                        'category' => 'Hatchery',
                        'type' => 'warning',
                        'title' => 'Day 18 Candling & Transfer Due',
                        'message' => "Batch #{$t['id']} ({$t['customer_name']}) has reached Day {$t['days_elapsed']} in setter. Move eggs to hatcher basket.",
                        'link' => url("/incubation-jobs/{$t['id']}"),
                        'time' => "Day {$t['days_elapsed']}",
                        'is_urgent' => true,
                        'is_seen' => false,
                    ];
                }
            }

            // 3. Hatch Day Today or Countdown <= 2 Days
            $hatchStmt = $pdo->query("
                SELECT j.id, j.expected_hatch_date, j.eggs_incubated, c.name AS customer_name,
                       DATEDIFF(j.expected_hatch_date, CURDATE()) AS days_left
                FROM incubation_jobs j
                JOIN customers c ON c.id = j.customer_id
                WHERE j.status IN ('received', 'incubating', 'hatching')
                  AND j.expected_hatch_date <= DATE_ADD(CURDATE(), INTERVAL 2 DAY)
                  AND j.expected_hatch_date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                ORDER BY j.expected_hatch_date ASC
                LIMIT 4
            ");
            if ($hatchStmt) {
                while ($h = $hatchStmt->fetch(PDO::FETCH_ASSOC)) {
                    $days = (int) $h['days_left'];
                    $urgent = $days <= 0;
                    $key = 'hatch_' . $h['id'] . '_' . date('Ymd');
                    $alerts[] = [
                        'id' => $key,
                        'key' => $key,
                        'category' => 'Hatchery',
                        'type' => $urgent ? 'danger' : 'warning',
                        'title' => $urgent ? 'Hatch Day Today!' : "Hatch Due in {$days} Day(s)",
                        'message' => "Job #{$h['id']} ({$h['customer_name']}) — " . number_format((int)$h['eggs_incubated']) . " chicks hatching for collection.",
                        'link' => url("/incubation-jobs/{$h['id']}"),
                        'time' => $urgent ? 'Today' : date('d M', strtotime((string)$h['expected_hatch_date'])),
                        'is_urgent' => $urgent,
                        'is_seen' => false,
                    ];
                }
            }

            // 4. Flock High Mortality Alerts
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
                    $key = 'mort_' . $m['id'] . '_' . $m['event_date'];
                    $alerts[] = [
                        'id' => $key,
                        'key' => $key,
                        'category' => 'Flock',
                        'type' => 'danger',
                        'title' => 'High Mortality Spike',
                        'message' => "Flock {$m['batch_code']}: {$m['quantity']} bird deaths recorded on " . date('d-M', strtotime((string)$m['event_date'])),
                        'link' => url("/flocks/{$m['id']}"),
                        'time' => date('d M', strtotime((string)$m['event_date'])),
                        'is_urgent' => true,
                        'is_seen' => false,
                    ];
                }
            }

            // 5. Aged Debtors (> 30 days pending, excluding internal Msantha owner)
            $debtStmt = $pdo->query("
                SELECT c.id, c.name,
                       CAST(COALESCE(j.incubation_cost, 0) - COALESCE(j.incubation_paid, 0) AS SIGNED) AS bal
                FROM customers c
                JOIN (
                    SELECT customer_id, SUM(cost) AS incubation_cost, SUM(amount_paid) AS incubation_paid
                    FROM incubation_jobs
                    WHERE COALESCE(date_hatched, date_received, created_at) <= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
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
                    $key = 'debt_' . $d['id'];
                    $alerts[] = [
                        'id' => $key,
                        'key' => $key,
                        'category' => 'Finance',
                        'type' => 'warning',
                        'title' => 'Overdue Customer Balance (>30d)',
                        'message' => "{$d['name']} has an uncollected balance of " . number_format((int)$d['bal']) . " MWK.",
                        'link' => url("/customers/{$d['id']}"),
                        'time' => 'Overdue',
                        'is_urgent' => false,
                        'is_seen' => false,
                    ];
                }
            }
        } catch (Throwable) {
            // graceful fallback
        }

        return $alerts;
    }

    /**
     * Mark an alert or all alerts as seen for a user.
     * Seen alerts leave a trail in the dropdown but stop counting on the bell badge.
     */
    public static function markAsSeen(int $userId, ?string $key = null): void
    {
        if ($userId <= 0) {
            return;
        }

        try {
            $pdo = DB::pdo();
            if ($key !== null && $key !== '' && $key !== 'all') {
                $stmt = $pdo->prepare("
                    INSERT INTO user_notification_status (user_id, notification_key, is_seen)
                    VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE is_seen = 1
                ");
                $stmt->execute([$userId, $key]);
            } else {
                // Mark all active reminders as seen
                $all = self::collectAllReminders();
                $stmt = $pdo->prepare("
                    INSERT INTO user_notification_status (user_id, notification_key, is_seen)
                    VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE is_seen = 1
                ");
                foreach ($all as $item) {
                    $stmt->execute([$userId, $item['key']]);
                }
            }
        } catch (Throwable) {
            // ignore
        }
    }

    /**
     * Dismiss / delete an alert permanently so it leaves the trail entirely.
     */
    public static function dismiss(int $userId, string $key): void
    {
        if ($userId <= 0 || $key === '') {
            return;
        }

        try {
            $pdo = DB::pdo();
            if ($key === 'all') {
                $all = self::collectAllReminders();
                $stmt = $pdo->prepare("
                    INSERT INTO user_notification_status (user_id, notification_key, is_seen, is_deleted)
                    VALUES (?, ?, 1, 1)
                    ON DUPLICATE KEY UPDATE is_seen = 1, is_deleted = 1
                ");
                foreach ($all as $item) {
                    $stmt->execute([$userId, $item['key']]);
                }
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO user_notification_status (user_id, notification_key, is_seen, is_deleted)
                    VALUES (?, ?, 1, 1)
                    ON DUPLICATE KEY UPDATE is_seen = 1, is_deleted = 1
                ");
                $stmt->execute([$userId, $key]);
            }
        } catch (Throwable) {
            // ignore
        }
    }
}
