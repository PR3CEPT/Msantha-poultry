<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\DB;
use PDO;

class PricingService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?: DB::pdo();
    }

    /**
     * Returns the selling price for a bird of given breed at an age (in weeks) on a specific date.
     */
    public function getPrice(int $breedId, int $ageInWeeks, ?string $date = null): int
    {
        $date = $date ?: date('Y-m-d');

        $stmt = $this->pdo->prepare("
            SELECT price 
            FROM price_ladder
            WHERE breed_id = ? AND from_week <= ? AND effective_from <= ?
            ORDER BY from_week DESC, effective_from DESC
            LIMIT 1
        ");
        $stmt->execute([$breedId, $ageInWeeks, $date]);
        $price = $stmt->fetchColumn();

        if ($price !== false && $price !== null) {
            return (int) $price;
        }

        // Fallback: check breed's base or tray price or standard default
        $bStmt = $this->pdo->prepare("SELECT egg_tray_price FROM breeds WHERE id = ?");
        $bStmt->execute([$breedId]);
        $trayPrice = $bStmt->fetchColumn();

        if ($trayPrice && (int)$trayPrice > 0) {
            // Estimate live bird price around tray price or 5,000 MWK baseline
            return max(5000, (int) $trayPrice);
        }

        return 5000; // Baseline fallback (5,000 MWK)
    }

    /**
     * Retrieve the full price ladder for a breed
     */
    public function getLadder(int $breedId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM price_ladder 
            WHERE breed_id = ? 
            ORDER BY from_week ASC, effective_from DESC
        ");
        $stmt->execute([$breedId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add or update an age tier in the price ladder
     */
    public function addTier(int $breedId, int $fromWeek, int $price, string $effectiveFrom, ?int $userId = null): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO price_ladder (breed_id, from_week, price, effective_from)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$breedId, $fromWeek, $price, $effectiveFrom]);
        $id = (int) $this->pdo->lastInsertId();

        Audit::log('create', 'price_ladder', $id, null, [
            'breed_id' => $breedId,
            'from_week' => $fromWeek,
            'price' => $price,
            'effective_from' => $effectiveFrom,
        ], $userId);

        return $id;
    }
}

