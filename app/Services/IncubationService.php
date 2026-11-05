<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\DB;
use InvalidArgumentException;
use PDO;
use RuntimeException;

class IncubationService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?: DB::pdo();
    }

    /**
     * Compute transfer day and expected hatch date based on species.
     * Species incubation days:
     * - Chicken: 21 days (transfer day 18 = +18 days, hatch day 21 = +21 days)
     * - Turkey: 28 days (transfer day 25 = +25 days, hatch day 28 = +28 days)
     * - Duck: 28 days (transfer day 25 = +25 days, hatch day 28 = +28 days)
     * - Quail: 18 days (transfer day 15 = +15 days, hatch day 18 = +18 days)
     */
    public function calculateDates(int $speciesId, string $dateIncubated): array
    {
        $stmt = $this->pdo->prepare("SELECT incubation_days FROM species WHERE id = ?");
        $stmt->execute([$speciesId]);
        $incDays = $stmt->fetchColumn();

        $incubationDays = $incDays !== false ? (int) $incDays : 21;
        $transferDays = max(1, $incubationDays - 3);

        $dateIncTs = strtotime($dateIncubated);
        if ($dateIncTs === false) {
            throw new InvalidArgumentException("Invalid incubation date: {$dateIncubated}");
        }

        $transferDate = date('Y-m-d', strtotime("+{$transferDays} days", $dateIncTs));
        $hatchDate = date('Y-m-d', strtotime("+{$incubationDays} days", $dateIncTs));

        return [
            'date_transfer' => $transferDate,
            'expected_hatch_date' => $hatchDate,
            'incubation_days' => $incubationDays,
            'transfer_days' => $transferDays,
        ];
    }

    /**
     * Cost = eggs_incubated * rate_per_egg
     */
    public function calculateCost(int $eggsIncubated, int $ratePerEgg): int
    {
        if ($eggsIncubated < 0 || $ratePerEgg < 0) {
            throw new InvalidArgumentException("Eggs and rate must be non-negative integers.");
        }
        return $eggsIncubated * $ratePerEgg;
    }

    /**
     * Compute hatch percent: (hatched / eggs_incubated) * 100
     */
    public function calculateHatchPercent(int $eggsIncubated, int $hatched): float
    {
        if ($eggsIncubated <= 0) {
            return 0.0;
        }
        return round(($hatched / $eggsIncubated) * 100, 2);
    }

    /**
     * Automatic allocation of incubation payments:
     * 25% electricity, 15% water, 30% labour, 20% telephone, 10% profit
     * Exact integer cents/MWK rounding: remainder always added to profit
     * so that electricity + water + labour + telephone + profit === amount.
     */
    public function calculateAllocation(int $amount): array
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Payment amount cannot be negative.");
        }

        $elec = (int) floor($amount * 0.25);
        $water = (int) floor($amount * 0.15);
        $labour = (int) floor($amount * 0.30);
        $tel = (int) floor($amount * 0.20);
        
        // Remainder goes to profit
        $profit = $amount - ($elec + $water + $labour + $tel);

        return [
            'electricity' => $elec,
            'water' => $water,
            'labour' => $labour,
            'telephone' => $tel,
            'profit' => $profit,
        ];
    }

    /**
     * Record payment and automatically store incubation allocations
     */
    public function recordPayment(int $jobId, int $amount, string $method, string $paidOn, ?int $receivedBy = null): int
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Payment amount must be greater than zero.");
        }

        $allowedMethods = ['cash', 'airtel_money', 'tnm_mpamba', 'bank', 'paychangu', 'other'];
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'cash';
        }

        $this->pdo->beginTransaction();
        try {
            // Check job exists
            $jobStmt = $this->pdo->prepare("SELECT id, cost, amount_paid FROM incubation_jobs WHERE id = ? FOR UPDATE");
            $jobStmt->execute([$jobId]);
            $job = $jobStmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                throw new RuntimeException("Incubation job #{$jobId} not found.");
            }

            // Insert payment
            $pStmt = $this->pdo->prepare("
                INSERT INTO incubation_payments (job_id, paid_on, amount, method, received_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $pStmt->execute([$jobId, $paidOn, $amount, $method, $receivedBy]);
            $paymentId = (int) $this->pdo->lastInsertId();

            // Calculate allocations
            $splits = $this->calculateAllocation($amount);
            $allocStmt = $this->pdo->prepare("
                INSERT INTO incubation_allocations (payment_id, category, amount)
                VALUES (?, ?, ?)
            ");

            foreach ($splits as $cat => $allocAmount) {
                $allocStmt->execute([$paymentId, $cat, $allocAmount]);
            }

            // Update job amount_paid
            $newPaid = (int) $job['amount_paid'] + $amount;
            $updateJob = $this->pdo->prepare("UPDATE incubation_jobs SET amount_paid = ? WHERE id = ?");
            $updateJob->execute([$newPaid, $jobId]);

            $this->pdo->commit();

            Audit::log('create', 'incubation_payments', $paymentId, null, [
                'job_id' => $jobId,
                'amount' => $amount,
                'method' => $method,
                'allocations' => $splits,
            ], $receivedBy);

            return $paymentId;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Record candling and hatch results
     */
    public function recordCandlingAndHatch(
        int $jobId,
        ?int $infertile,
        ?int $fertile,
        ?int $hatched,
        ?string $dateHatched = null,
        ?int $userId = null
    ): void {
        $stmt = $this->pdo->prepare("SELECT id, eggs_incubated, status FROM incubation_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            throw new RuntimeException("Job #{$jobId} not found.");
        }

        $dateHatched = $dateHatched ?: date('Y-m-d');
        $newStatus = ($hatched !== null && $hatched >= 0) ? 'completed' : 'hatching';

        $update = $this->pdo->prepare("
            UPDATE incubation_jobs
            SET infertile = ?, fertile = ?, hatched = ?, date_hatched = ?, status = ?
            WHERE id = ?
        ");
        $update->execute([$infertile, $fertile, $hatched, $dateHatched, $newStatus, $jobId]);

        Audit::log('update', 'incubation_jobs', $jobId, $job, [
            'infertile' => $infertile,
            'fertile' => $fertile,
            'hatched' => $hatched,
            'date_hatched' => $dateHatched,
            'status' => $newStatus,
        ], $userId);
    }
}

