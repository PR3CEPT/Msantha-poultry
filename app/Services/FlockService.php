<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\DB;
use InvalidArgumentException;
use PDO;
use RuntimeException;

class FlockService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?: DB::pdo();
    }

    /**
     * Compute flock age in days, weeks, and months from start_date
     */
    public function computeAge(string $startDate, ?string $asOfDate = null): array
    {
        $startTs = strtotime($startDate);
        $asOfTs = strtotime($asOfDate ?: date('Y-m-d'));

        if ($startTs === false || $asOfTs === false) {
            return ['days' => 0, 'weeks' => 0, 'months' => 0];
        }

        $diffSeconds = max(0, $asOfTs - $startTs);
        $days = (int) floor($diffSeconds / 86400);
        $weeks = (int) floor($days / 7);
        $months = (int) floor($days / 30.4375);

        return [
            'days' => $days,
            'weeks' => $weeks,
            'months' => $months,
        ];
    }

    /**
     * Record a quick daily entry with deaths, sales, additions, cull, consumed, vaccination, and note.
     * Checks if mortality rate exceeds threshold (default 2% per day).
     */
    public function recordDailyEntry(int $flockId, string $date, array $entry, ?int $userId = null): array
    {
        // Get current stock
        $statusStmt = $this->pdo->prepare("SELECT current_stock, batch_code FROM v_flock_status WHERE flock_id = ?");
        $statusStmt->execute([$flockId]);
        $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

        if (!$statusRow) {
            throw new RuntimeException("Flock #{$flockId} not found.");
        }

        $stockBefore = (int) $statusRow['current_stock'];
        $deaths = max(0, (int) ($entry['deaths'] ?? 0));
        $sold = max(0, (int) ($entry['sold'] ?? 0));
        $soldPrice = !empty($entry['sold_price']) ? (int) $entry['sold_price'] : null;
        $customerId = !empty($entry['customer_id']) ? (int) $entry['customer_id'] : null;
        $added = max(0, (int) ($entry['added'] ?? 0));
        $culled = max(0, (int) ($entry['culled'] ?? 0));
        $consumed = max(0, (int) ($entry['consumed'] ?? 0));
        $vaccine = trim((string) ($entry['vaccine'] ?? ''));
        $remarks = trim((string) ($entry['remarks'] ?? ''));

        $this->pdo->beginTransaction();
        try {
            $createdEvents = [];

            // 1. Deaths
            if ($deaths > 0) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO flock_events (flock_id, event_date, event_type, quantity, remarks, created_by, created_at)
                    VALUES (?, ?, 'death', ?, ?, ?, NOW())
                ");
                $stmt->execute([$flockId, $date, $deaths, $remarks ?: 'Daily mortality', $userId]);
                $createdEvents['death'] = (int) $this->pdo->lastInsertId();
            }

            // 2. Sold
            if ($sold > 0) {
                $totalAmount = $soldPrice ? ($sold * $soldPrice) : null;
                $stmt = $this->pdo->prepare("
                    INSERT INTO flock_events (flock_id, event_date, event_type, quantity, unit_price, amount, customer_id, remarks, created_by, created_at)
                    VALUES (?, ?, 'sale', ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$flockId, $date, $sold, $soldPrice, $totalAmount, $customerId, $remarks ?: 'Bird sales', $userId]);
                $createdEvents['sale'] = (int) $this->pdo->lastInsertId();
            }

            // 3. Added
            if ($added > 0) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO flock_events (flock_id, event_date, event_type, quantity, remarks, created_by, created_at)
                    VALUES (?, ?, 'addition', ?, ?, ?, NOW())
                ");
                $stmt->execute([$flockId, $date, $added, $remarks ?: 'Addition to flock', $userId]);
                $createdEvents['addition'] = (int) $this->pdo->lastInsertId();
            }

            // 4. Culled
            if ($culled > 0) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO flock_events (flock_id, event_date, event_type, quantity, remarks, created_by, created_at)
                    VALUES (?, ?, 'cull', ?, ?, ?, NOW())
                ");
                $stmt->execute([$flockId, $date, $culled, $remarks ?: 'Culled', $userId]);
                $createdEvents['cull'] = (int) $this->pdo->lastInsertId();
            }

            // 5. Consumed
            if ($consumed > 0) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO flock_events (flock_id, event_date, event_type, quantity, remarks, created_by, created_at)
                    VALUES (?, ?, 'consumed', ?, ?, ?, NOW())
                ");
                $stmt->execute([$flockId, $date, $consumed, $remarks ?: 'Staff / Farm consumption', $userId]);
                $createdEvents['consumed'] = (int) $this->pdo->lastInsertId();
            }

            // 6. Vaccination
            if ($vaccine !== '') {
                $stmt = $this->pdo->prepare("
                    INSERT INTO flock_events (flock_id, event_date, event_type, quantity, remarks, created_by, created_at)
                    VALUES (?, ?, 'vaccination', 0, ?, ?, NOW())
                ");
                $stmt->execute([$flockId, $date, "Vaccination: {$vaccine}" . ($remarks ? " - {$remarks}" : ""), $userId]);
                $createdEvents['vaccination'] = (int) $this->pdo->lastInsertId();
            }

            // 7. Note only
            if (empty($createdEvents) && $remarks !== '') {
                $stmt = $this->pdo->prepare("
                    INSERT INTO flock_events (flock_id, event_date, event_type, quantity, remarks, created_by, created_at)
                    VALUES (?, ?, 'note', 0, ?, ?, NOW())
                ");
                $stmt->execute([$flockId, $date, $remarks, $userId]);
                $createdEvents['note'] = (int) $this->pdo->lastInsertId();
            }

            $this->pdo->commit();

            // Mortality rate & Alert check
            $mortalityRate = $stockBefore > 0 ? round(($deaths / $stockBefore) * 100, 2) : 0.0;
            $threshold = 2.0; // 2% per day alert threshold
            $alert = ($mortalityRate >= $threshold);

            Audit::log('create', 'flock_daily_entry', $flockId, null, [
                'flock_id' => $flockId,
                'date' => $date,
                'deaths' => $deaths,
                'sold' => $sold,
                'mortality_rate' => $mortalityRate,
                'alert' => $alert,
            ], $userId);

            return [
                'flock_id' => $flockId,
                'date' => $date,
                'events_created' => $createdEvents,
                'mortality_rate' => $mortalityRate,
                'mortality_alert' => $alert,
                'stock_before' => $stockBefore,
                'stock_after' => max(0, $stockBefore - $deaths - $sold - $culled - $consumed + $added),
            ];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Get flock trajectory: daily stock and cumulative mortality timeline for Chart.js
     */
    public function getFlockTrajectory(int $flockId): array
    {
        $stmt = $this->pdo->prepare("SELECT start_count, start_date FROM flocks WHERE id = ?");
        $stmt->execute([$flockId]);
        $flock = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$flock) {
            return ['dates' => [], 'stock' => [], 'cumulative_deaths' => []];
        }

        $initStock = (int) ($flock['start_count'] ?? $flock['starting_count'] ?? 0);

        // Check if legacy daily rows exist for this flock
        $legacyRows = $this->pdo->prepare("
            SELECT record_date, balance, deaths_todate, deaths, sold
            FROM flock_daily_legacy
            WHERE flock_id = ? AND row_kind = 'day' AND is_projected = 0
            ORDER BY excel_row ASC
        ");
        $legacyRows->execute([$flockId]);
        $rows = $legacyRows->fetchAll(PDO::FETCH_ASSOC);

        $dates = [];
        $stock = [];
        $cumDeaths = [];
        $cumSold = [];
        $dailySold = [];

        if (!empty($rows)) {
            $runningDeaths = 0;
            $runningSold = 0;
            foreach ($rows as $r) {
                if (!empty($r['record_date'])) {
                    $dates[] = date('d-M-y', strtotime($r['record_date']));
                    $stock[] = (int) ($r['balance'] ?? 0);
                    if ($r['deaths_todate'] !== null) {
                        $cumDeaths[] = (int) $r['deaths_todate'];
                    } else {
                        $runningDeaths += (int) ($r['deaths'] ?? 0);
                        $cumDeaths[] = $runningDeaths;
                    }
                    $s = (int) ($r['sold'] ?? 0);
                    $runningSold += $s;
                    $cumSold[] = $runningSold;
                    $dailySold[] = $s;
                }
            }
        } else {
            // Aggregate from flock_events
            $eventsStmt = $this->pdo->prepare("
                SELECT event_date, event_type, quantity
                FROM flock_events
                WHERE flock_id = ?
                ORDER BY event_date ASC, id ASC
            ");
            $eventsStmt->execute([$flockId]);
            $events = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);

            $current = $initStock;
            $deathsTotal = 0;
            $soldTotal = 0;
            $dates[] = date('d-M-y', strtotime($flock['start_date']));
            $stock[] = $current;
            $cumDeaths[] = 0;
            $cumSold[] = 0;
            $dailySold[] = 0;

            foreach ($events as $ev) {
                $evDate = date('d-M-y', strtotime($ev['event_date']));
                $qty = (int) $ev['quantity'];
                $sToday = 0;
                if ($ev['event_type'] === 'death') {
                    $current -= $qty;
                    $deathsTotal += $qty;
                } elseif (in_array($ev['event_type'], ['sale', 'cull', 'consumed'], true)) {
                    $current -= $qty;
                    if ($ev['event_type'] === 'sale') {
                        $soldTotal += $qty;
                        $sToday = $qty;
                    }
                } elseif ($ev['event_type'] === 'addition') {
                    $current += $qty;
                }
                $dates[] = $evDate;
                $stock[] = max(0, $current);
                $cumDeaths[] = $deathsTotal;
                $cumSold[] = $soldTotal;
                $dailySold[] = $sToday;
            }
        }

        return [
            'dates' => $dates,
            'stock' => $stock,
            'cumulative_deaths' => $cumDeaths,
            'cumulative_sold' => $cumSold,
            'daily_sold' => $dailySold,
        ];
    }

    /**
     * Close a flock batch
     */
    public function closeFlock(int $flockId, ?string $notes = null, ?int $userId = null): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE flocks 
            SET status = 'closed', closed_date = CURDATE(), notes = CONCAT_WS('; ', notes, ?)
            WHERE id = ?
        ");
        $stmt->execute([$notes ?: 'Batch closed by user', $flockId]);

        Audit::log('update', 'flocks', $flockId, null, [
            'status' => 'closed',
            'closed_date' => date('Y-m-d'),
            'notes' => $notes,
        ], $userId);
    }
}

