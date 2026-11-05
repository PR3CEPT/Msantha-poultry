<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\DB;
use App\Core\Logger;
use InvalidArgumentException;
use PDO;
use Throwable;

class OfflineSyncService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? DB::pdo();
    }

    /**
     * Process a batch of offline synchronization entries.
     *
     * @param array<int, array<string, mixed>> $items
     * @param int|null $userId
     * @return array<string, mixed>
     */
    public function syncBatch(array $items, ?int $userId = null): array
    {
        $results = [];
        $synced = 0;
        $duplicates = 0;
        $failed = 0;

        foreach ($items as $index => $item) {
            $clientUuid = trim((string) ($item['client_uuid'] ?? ''));
            $recordType = trim((string) ($item['type'] ?? ''));
            $payload = is_array($item['payload'] ?? null) ? $item['payload'] : [];

            if ($clientUuid === '' || !in_array($recordType, ['death', 'egg', 'sale'], true)) {
                $failed++;
                $results[] = [
                    'client_uuid' => $clientUuid,
                    'status' => 'failed',
                    'error' => 'Invalid client_uuid or record type',
                ];
                continue;
            }

            // 1. Idempotency check: see if client_uuid has already been logged
            $stmt = $this->pdo->prepare("SELECT id, status, entity_id FROM offline_sync_log WHERE client_uuid = :uuid LIMIT 1");
            $stmt->execute(['uuid' => $clientUuid]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing && $existing['status'] === 'success') {
                $duplicates++;
                $results[] = [
                    'client_uuid' => $clientUuid,
                    'status' => 'already_synced',
                    'entity_id' => (int) $existing['entity_id'],
                ];
                continue;
            }

            // 2. Process record inside transaction
            try {
                $this->pdo->beginTransaction();

                $entityId = null;
                if ($recordType === 'death') {
                    $entityId = $this->processDeath($payload, $userId);
                } elseif ($recordType === 'egg') {
                    $entityId = $this->processEgg($payload, $userId);
                } elseif ($recordType === 'sale') {
                    $entityId = $this->processSale($payload, $userId);
                }

                // Log successful sync
                $logStmt = $this->pdo->prepare("
                    INSERT INTO offline_sync_log (client_uuid, record_type, entity_id, user_id, status, payload_json, synced_at)
                    VALUES (:uuid, :type, :entity_id, :user_id, 'success', :payload, NOW())
                    ON DUPLICATE KEY UPDATE status = 'success', entity_id = :entity_id2, synced_at = NOW()
                ");
                $logStmt->execute([
                    'uuid' => $clientUuid,
                    'type' => $recordType,
                    'entity_id' => $entityId,
                    'user_id' => $userId,
                    'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    'entity_id2' => $entityId,
                ]);

                $this->pdo->commit();
                $synced++;

                $results[] = [
                    'client_uuid' => $clientUuid,
                    'status' => 'success',
                    'entity_id' => $entityId,
                ];
            } catch (Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                $failed++;
                Logger::error("Offline sync error on UUID {$clientUuid}: " . $e->getMessage());

                // Record failure in offline_sync_log
                try {
                    $failStmt = $this->pdo->prepare("
                        INSERT INTO offline_sync_log (client_uuid, record_type, user_id, status, payload_json, error_message, synced_at)
                        VALUES (:uuid, :type, :user_id, 'failed', :payload, :err, NOW())
                        ON DUPLICATE KEY UPDATE status = 'failed', error_message = :err2, synced_at = NOW()
                    ");
                    $failStmt->execute([
                        'uuid' => $clientUuid,
                        'type' => $recordType,
                        'user_id' => $userId,
                        'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                        'err' => substr($e->getMessage(), 0, 500),
                        'err2' => substr($e->getMessage(), 0, 500),
                    ]);
                } catch (Throwable $ignore) {
                    // Ignore failure to log error
                }

                $results[] = [
                    'client_uuid' => $clientUuid,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => true,
            'total' => count($items),
            'synced' => $synced,
            'duplicates' => $duplicates,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * Process offline death event
     */
    private function processDeath(array $payload, ?int $userId): int
    {
        $flockId = (int) ($payload['flock_id'] ?? 0);
        $eventDate = trim((string) ($payload['event_date'] ?? date('Y-m-d')));
        $quantity = (int) ($payload['quantity'] ?? 0);
        $remarks = trim((string) ($payload['remarks'] ?? 'Offline mortality entry'));

        if ($flockId <= 0 || $quantity <= 0) {
            throw new InvalidArgumentException("Invalid flock_id ({$flockId}) or quantity ({$quantity}) for death entry");
        }

        // Verify flock exists
        $check = $this->pdo->prepare("SELECT id FROM flocks WHERE id = :id LIMIT 1");
        $check->execute(['id' => $flockId]);
        if (!$check->fetch()) {
            throw new InvalidArgumentException("Flock #{$flockId} not found");
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO flock_events (flock_id, event_date, event_type, quantity, remarks, created_by, created_at)
            VALUES (:flock_id, :event_date, 'death', :quantity, :remarks, :user_id, NOW())
        ");
        $stmt->execute([
            'flock_id' => $flockId,
            'event_date' => $eventDate,
            'quantity' => $quantity,
            'remarks' => $remarks !== '' ? $remarks : null,
            'user_id' => $userId,
        ]);

        $eventId = (int) $this->pdo->lastInsertId();
        Audit::log('create', 'flock_events', $eventId, null, [
            'flock_id' => $flockId,
            'event_type' => 'death',
            'quantity' => $quantity,
            'event_date' => $eventDate,
        ], $userId);

        return $eventId;
    }

    /**
     * Process offline egg collection entry
     */
    private function processEgg(array $payload, ?int $userId): int
    {
        $flockId = !empty($payload['flock_id']) ? (int) $payload['flock_id'] : null;
        $breedId = !empty($payload['breed_id']) ? (int) $payload['breed_id'] : null;
        $prodDate = trim((string) ($payload['prod_date'] ?? date('Y-m-d')));
        $collected = (int) ($payload['collected'] ?? 0);
        $broken = (int) ($payload['broken'] ?? 0);
        $consumed = (int) ($payload['consumed'] ?? 0);
        $sold = (int) ($payload['sold'] ?? 0);
        $incubated = (int) ($payload['incubated'] ?? 0);
        $kept = (int) ($payload['kept'] ?? 0);
        $remarks = trim((string) ($payload['remarks'] ?? 'Offline egg collection'));

        // If breed_id is missing, infer from flock
        if (!$breedId && $flockId) {
            $fStmt = $this->pdo->prepare("SELECT breed_id FROM flocks WHERE id = :id LIMIT 1");
            $fStmt->execute(['id' => $flockId]);
            $bRow = $fStmt->fetch(PDO::FETCH_ASSOC);
            if ($bRow) {
                $breedId = (int) $bRow['breed_id'];
            }
        }

        if (!$breedId || $collected < 0) {
            throw new InvalidArgumentException("Invalid breed_id or collected eggs quantity");
        }

        // Insert or update on duplicate (breed_id, flock_id, prod_date)
        $stmt = $this->pdo->prepare("
            INSERT INTO egg_production_daily (
                breed_id, flock_id, prod_date, collected, broken, consumed, sold, incubated, kept, remarks
            ) VALUES (
                :breed_id, :flock_id, :prod_date, :collected, :broken, :consumed, :sold, :incubated, :kept, :remarks
            ) ON DUPLICATE KEY UPDATE
                collected = collected + VALUES(collected),
                broken = broken + VALUES(broken),
                consumed = consumed + VALUES(consumed),
                sold = sold + VALUES(sold),
                incubated = incubated + VALUES(incubated),
                kept = kept + VALUES(kept),
                remarks = CONCAT(COALESCE(remarks, ''), ' | ', VALUES(remarks))
        ");
        $stmt->execute([
            'breed_id' => $breedId,
            'flock_id' => $flockId,
            'prod_date' => $prodDate,
            'collected' => $collected,
            'broken' => $broken,
            'consumed' => $consumed,
            'sold' => $sold,
            'incubated' => $incubated,
            'kept' => $kept,
            'remarks' => $remarks !== '' ? $remarks : null,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        if ($id === 0) {
            // Find existing row ID
            $sel = $this->pdo->prepare("SELECT id FROM egg_production_daily WHERE breed_id = :b AND prod_date = :d AND ((flock_id IS NULL AND :f1 IS NULL) OR flock_id = :f2) LIMIT 1");
            $sel->execute(['b' => $breedId, 'd' => $prodDate, 'f1' => $flockId, 'f2' => $flockId]);
            $id = (int) ($sel->fetchColumn() ?: 0);
        }

        Audit::log('create', 'egg_production_daily', $id, null, [
            'breed_id' => $breedId,
            'flock_id' => $flockId,
            'prod_date' => $prodDate,
            'collected' => $collected,
        ], $userId);

        return $id;
    }

    /**
     * Process offline point of sale entry
     */
    private function processSale(array $payload, ?int $userId): int
    {
        $saleDate = trim((string) ($payload['sale_date'] ?? date('Y-m-d')));
        $customerId = !empty($payload['customer_id']) ? (int) $payload['customer_id'] : null;
        $paymentMethod = trim((string) ($payload['payment_method'] ?? 'cash'));
        $validMethods = ['cash', 'airtel_money', 'tnm_mpamba', 'bank', 'paychangu', 'credit'];
        if (!in_array($paymentMethod, $validMethods, true)) {
            $paymentMethod = 'cash';
        }

        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        if (empty($items)) {
            throw new InvalidArgumentException("Sale must have at least one line item");
        }

        // Calculate totals
        $total = 0;
        $processedItems = [];
        $validItemTypes = ['live_birds', 'fertilised_eggs', 'table_eggs', 'stale_eggs', 'chicks', 'manure', 'other'];

        foreach ($items as $it) {
            $itemType = trim((string) ($it['item_type'] ?? 'table_eggs'));
            if (!in_array($itemType, $validItemTypes, true)) {
                $itemType = 'other';
            }
            $quantity = (int) ($it['quantity'] ?? 0);
            $unitPrice = (int) ($it['unit_price'] ?? 0);
            if ($quantity <= 0 || $unitPrice <= 0) {
                continue;
            }
            $amount = $quantity * $unitPrice;
            $total += $amount;

            $processedItems[] = [
                'item_type' => $itemType,
                'breed_id' => !empty($it['breed_id']) ? (int) $it['breed_id'] : null,
                'flock_id' => !empty($it['flock_id']) ? (int) $it['flock_id'] : null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $amount,
            ];
        }

        if (empty($processedItems)) {
            throw new InvalidArgumentException("No valid sale items provided");
        }

        $paid = ($paymentMethod !== 'credit') ? $total : 0;

        // Insert into sales
        $sStmt = $this->pdo->prepare("
            INSERT INTO sales (sale_date, customer_id, total, paid, payment_method, created_by)
            VALUES (:sale_date, :customer_id, :total, :paid, :payment_method, :created_by)
        ");
        $sStmt->execute([
            'sale_date' => $saleDate,
            'customer_id' => $customerId,
            'total' => $total,
            'paid' => $paid,
            'payment_method' => $paymentMethod,
            'created_by' => $userId,
        ]);

        $saleId = (int) $this->pdo->lastInsertId();

        // Insert line items
        $iStmt = $this->pdo->prepare("
            INSERT INTO sale_items (sale_id, item_type, breed_id, flock_id, quantity, unit_price, amount)
            VALUES (:sale_id, :item_type, :breed_id, :flock_id, :quantity, :unit_price, :amount)
        ");
        foreach ($processedItems as $pi) {
            $iStmt->execute([
                'sale_id' => $saleId,
                'item_type' => $pi['item_type'],
                'breed_id' => $pi['breed_id'],
                'flock_id' => $pi['flock_id'],
                'quantity' => $pi['quantity'],
                'unit_price' => $pi['unit_price'],
                'amount' => $pi['amount'],
            ]);
        }

        // If cash sale, record in cash_entries for POULTRY
        if ($paymentMethod === 'cash') {
            $buStmt = $this->pdo->prepare("SELECT id FROM business_units WHERE code = 'POULTRY' LIMIT 1");
            $buStmt->execute();
            $buId = (int) ($buStmt->fetchColumn() ?: 1);

            $cStmt = $this->pdo->prepare("
                INSERT INTO cash_entries (business_unit_id, entry_date, description, receipt, expenditure, entry_kind, created_by)
                VALUES (:bu_id, :entry_date, :desc, :receipt, 0, 'transaction', :created_by)
            ");
            $cStmt->execute([
                'bu_id' => $buId,
                'entry_date' => $saleDate,
                'desc' => "Offline Sync Sale #{$saleId}",
                'receipt' => $total,
                'created_by' => $userId,
            ]);
        }

        Audit::log('create', 'sales', $saleId, null, [
            'total' => $total,
            'paid' => $paid,
            'payment_method' => $paymentMethod,
            'items_count' => count($processedItems),
        ], $userId);

        return $saleId;
    }
}

