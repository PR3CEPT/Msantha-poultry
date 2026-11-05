<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\DB;
use PDO;

class VaccinationService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?: DB::pdo();
        $this->ensureDefaultSchedules();
    }

    private function ensureDefaultSchedules(): void
    {
        $count = (int) $this->pdo->query("SELECT COUNT(*) FROM vaccination_schedule")->fetchColumn();
        if ($count === 0) {
            // Seed standard poultry vaccination schedule
            // Chicken: species_id = 1
            $defaults = [
                [1, 'Marek\'s Disease', 1, null],
                [1, 'Newcastle Disease (Hitchner B1)', 7, null],
                [1, 'Gumboro (IBD 1st dose)', 14, null],
                [1, 'Newcastle Disease (Lasota)', 21, null],
                [1, 'Gumboro (IBD 2nd dose)', 28, null],
                [1, 'Fowl Pox', 42, null],
                [1, 'Fowl Typhoid', 56, null],
                [1, 'Newcastle (Lasota booster)', 70, 60],
            ];
            $stmt = $this->pdo->prepare("
                INSERT INTO vaccination_schedule (species_id, vaccine, at_day, repeat_every_days)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($defaults as $d) {
                $stmt->execute($d);
            }
        }
    }

    /**
     * Compute scheduled vaccinations and identify which are due or overdue for a flock
     */
    public function getFlockVaccinations(int $flockId, ?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?: date('Y-m-d');

        $stmt = $this->pdo->prepare("
            SELECT f.id, f.batch_code, f.start_date, b.species_id
            FROM flocks f
            JOIN breeds b ON b.id = f.breed_id
            WHERE f.id = ?
        ");
        $stmt->execute([$flockId]);
        $flock = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$flock) {
            return [];
        }

        $schedStmt = $this->pdo->prepare("
            SELECT * FROM vaccination_schedule
            WHERE species_id = ?
            ORDER BY at_day ASC
        ");
        $schedStmt->execute([$flock['species_id']]);
        $templates = $schedStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch already administered vaccinations
        $givenStmt = $this->pdo->prepare("
            SELECT event_date, remarks
            FROM flock_events
            WHERE flock_id = ? AND event_type = 'vaccination'
        ");
        $givenStmt->execute([$flockId]);
        $givenEvents = $givenStmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        $startDateTs = strtotime($flock['start_date']);
        $asOfTs = strtotime($asOfDate);

        foreach ($templates as $t) {
            $dueTs = strtotime("+{$t['at_day']} days", $startDateTs);
            $dueDate = date('Y-m-d', $dueTs);

            $administered = false;
            foreach ($givenEvents as $ge) {
                if (stripos($ge['remarks'] ?? '', $t['vaccine']) !== false) {
                    $administered = true;
                    break;
                }
            }

            $isOverdue = (!$administered && $dueTs < $asOfTs);
            $isDueSoon = (!$administered && abs($dueTs - $asOfTs) <= 3 * 86400);

            $results[] = [
                'id' => $t['id'],
                'vaccine' => $t['vaccine'],
                'at_day' => (int) $t['at_day'],
                'due_date' => $dueDate,
                'administered' => $administered,
                'is_overdue' => $isOverdue,
                'is_due_soon' => $isDueSoon,
            ];
        }

        return $results;
    }

    /**
     * Record a vaccination event for a flock
     */
    public function recordVaccination(int $flockId, string $vaccine, string $date, ?string $remarks = null, ?int $userId = null): int
    {
        $fullRemarks = "Vaccination: {$vaccine}" . ($remarks ? " - {$remarks}" : "");

        $stmt = $this->pdo->prepare("
            INSERT INTO flock_events (flock_id, event_date, event_type, quantity, remarks, created_by, created_at)
            VALUES (?, ?, 'vaccination', 0, ?, ?, NOW())
        ");
        $stmt->execute([$flockId, $date, $fullRemarks, $userId]);
        $id = (int) $this->pdo->lastInsertId();

        Audit::log('create', 'flock_events', $id, null, [
            'flock_id' => $flockId,
            'event_type' => 'vaccination',
            'vaccine' => $vaccine,
            'date' => $date,
        ], $userId);

        return $id;
    }
}

