<?php

declare(strict_types=1);

namespace App\Services\SMS;

use App\Core\Audit;
use App\Core\DB;
use App\Core\Phone;
use PDO;

class SmsService
{
    private PDO $pdo;
    private SmsProviderInterface $provider;

    public function __construct(?PDO $pdo = null, ?SmsProviderInterface $provider = null)
    {
        $this->pdo = $pdo ?? DB::pdo();
        $this->provider = $provider ?? SmsProviderFactory::create($this->pdo);
    }

    public function getProvider(): SmsProviderInterface
    {
        return $this->provider;
    }

    public function setProvider(SmsProviderInterface $provider): void
    {
        $this->provider = $provider;
    }

    /**
     * Check if a customer is internal (farm-owned) or has opted out of SMS.
     */
    public function isCustomerSuppressed(int $customerId): bool
    {
        $stmt = $this->pdo->prepare("SELECT is_internal, sms_opt_in FROM customers WHERE id = ? LIMIT 1");
        $stmt->execute([$customerId]);
        $cust = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cust) {
            return false;
        }

        // Suppressed if farm internal (Msantha) or sms_opt_in is 0
        return ((int) $cust['is_internal'] === 1 || (int) $cust['sms_opt_in'] === 0);
    }

    /**
     * Retrieve active template body by code.
     */
    public function getTemplate(string $code): ?string
    {
        $stmt = $this->pdo->prepare("SELECT body FROM sms_templates WHERE code = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$code]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string) $val : null;
    }

    /**
     * Replace template placeholders with variables.
     * @param array<string, mixed> $vars
     */
    public function renderMessage(string $body, array $vars = []): string
    {
        $search = [];
        $replace = [];
        foreach ($vars as $k => $v) {
            $search[] = '{' . $k . '}';
            $replace[] = (string) $v;
        }
        return str_replace($search, $replace, $body);
    }

    /**
     * Calculate SMS segments (160 characters per standard GSM segment).
     */
    public function calculateSegments(string $message): int
    {
        $length = mb_strlen($message);
        if ($length <= 0) {
            return 1;
        }
        return (int) ceil($length / 160.0);
    }

    /**
     * Queue an SMS message into sms_outbox.
     * Enforces customer opt-in, internal farm exclusion, E.164 normalization, and deduplication.
     *
     * @param array<string, mixed> $vars
     */
    public function queue(
        string $templateCode,
        string $rawPhone,
        array $vars = [],
        ?string $dedupeKey = null,
        ?int $customerId = null,
        ?int $userId = null,
        ?string $relatedTable = null,
        ?int $relatedId = null
    ): ?int {
        // 1. Suppression check
        if ($customerId !== null && $this->isCustomerSuppressed($customerId)) {
            Audit::log('sms_suppressed', 'sms_outbox', null, null, [
                'customer_id' => $customerId,
                'template_code' => $templateCode,
                'reason' => 'Customer is marked internal or opted-out of SMS',
            ]);
            return null;
        }

        // 2. Phone normalization
        $phone = Phone::normalize($rawPhone);
        if ($phone === null) {
            return null;
        }

        // 3. Deduplication check
        if ($dedupeKey !== null && $dedupeKey !== '') {
            $chk = $this->pdo->prepare("SELECT id FROM sms_outbox WHERE dedupe_key = ? LIMIT 1");
            $chk->execute([$dedupeKey]);
            $existingId = $chk->fetchColumn();
            if ($existingId) {
                return (int) $existingId;
            }
        }

        // 4. Resolve template and render body
        $rawTemplate = $this->getTemplate($templateCode);
        $body = $rawTemplate !== null ? $this->renderMessage($rawTemplate, $vars) : ($vars['custom_message'] ?? $templateCode);

        // 5. Insert into outbox
        $stmt = $this->pdo->prepare("
            INSERT INTO sms_outbox (
                to_phone, customer_id, user_id, template_code, body, status, attempts,
                related_table, related_id, dedupe_key, send_after, created_at
            ) VALUES (?, ?, ?, ?, ?, 'queued', 0, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $phone,
            $customerId,
            $userId,
            $templateCode,
            $body,
            $relatedTable,
            $relatedId,
            $dedupeKey ?: null,
        ]);
        $outboxId = (int) $this->pdo->lastInsertId();

        Audit::log('queue', 'sms_outbox', $outboxId, null, [
            'to_phone' => $phone,
            'template_code' => $templateCode,
            'dedupe_key' => $dedupeKey,
            'segments' => $this->calculateSegments($body),
        ]);

        return $outboxId;
    }

    /**
     * Send queued messages with retry logic and exponential backoff.
     */
    public function sendQueued(int $limit = 50): int
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM sms_outbox 
            WHERE status = 'queued' AND send_after <= NOW() AND attempts < 3 
            ORDER BY id ASC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $successCount = 0;

        foreach ($messages as $msg) {
            $id = (int) $msg['id'];
            $attempts = (int) $msg['attempts'] + 1;
            $phone = (string) $msg['to_phone'];
            $body = (string) $msg['body'];

            $result = $this->provider->send($phone, $body);

            if ($result['success']) {
                $this->pdo->prepare("
                    UPDATE sms_outbox SET
                        status = 'sent',
                        attempts = ?,
                        sent_at = NOW(),
                        provider_msg_id = ?,
                        error = NULL
                    WHERE id = ?
                ")->execute([$attempts, $result['message_id'], $id]);

                $successCount++;
            } else {
                $errorMsg = substr((string) $result['error'], 0, 255);
                if ($attempts >= 3) {
                    $this->pdo->prepare("
                        UPDATE sms_outbox SET
                            status = 'failed',
                            attempts = ?,
                            error = ?
                        WHERE id = ?
                    ")->execute([$attempts, $errorMsg, $id]);
                } else {
                    // Backoff: 2 minutes * attempts
                    $backoffMinutes = $attempts * 2;
                    $this->pdo->prepare("
                        UPDATE sms_outbox SET
                            attempts = ?,
                            send_after = DATE_ADD(NOW(), INTERVAL ? MINUTE),
                            error = ?
                        WHERE id = ?
                    ")->execute([$attempts, $backoffMinutes, $errorMsg, $id]);
                }
            }
        }

        return $successCount;
    }

    /**
     * Reset a failed or pending message back to queued state.
     */
    public function retry(int $outboxId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE sms_outbox SET 
                status = 'queued',
                attempts = 0,
                send_after = NOW(),
                error = NULL
            WHERE id = ?
        ");
        $stmt->execute([$outboxId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Bulk queue direct message to selected customers.
     * @param array<int> $customerIds
     * @return array{queued: int, suppressed: int, segments: int}
     */
    public function bulkSend(array $customerIds, string $message, ?int $userId = null): array
    {
        $cleanMsg = trim($message);
        $segments = $this->calculateSegments($cleanMsg);
        $queued = 0;
        $suppressed = 0;

        foreach ($customerIds as $cId) {
            $cId = (int) $cId;
            if ($this->isCustomerSuppressed($cId)) {
                $suppressed++;
                continue;
            }

            $stmt = $this->pdo->prepare("SELECT phone, name FROM customers WHERE id = ? LIMIT 1");
            $stmt->execute([$cId]);
            $cust = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cust || empty($cust['phone'])) {
                $suppressed++;
                continue;
            }

            $rendered = str_replace('{customer_name}', (string) $cust['name'], $cleanMsg);
            $outId = $this->queue(
                'BULK_NOTICE',
                (string) $cust['phone'],
                ['custom_message' => $rendered],
                null,
                $cId,
                $userId
            );

            if ($outId !== null) {
                $queued++;
            } else {
                $suppressed++;
            }
        }

        return [
            'queued' => $queued,
            'suppressed' => $suppressed,
            'segments' => $segments,
        ];
    }

    /**
     * Get outbox list.
     * @return array<int, array<string, mixed>>
     */
    public function getOutbox(?string $status = null, ?string $phoneSearch = null, int $limit = 50, int $offset = 0): array
    {
        $where = " WHERE 1=1";
        $params = [];

        if ($status !== null && $status !== '' && $status !== 'all') {
            $where .= " AND o.status = ?";
            $params[] = $status;
        }

        if ($phoneSearch !== null && $phoneSearch !== '') {
            $where .= " AND (o.to_phone LIKE ? OR c.name LIKE ?)";
            $params[] = "%{$phoneSearch}%";
            $params[] = "%{$phoneSearch}%";
        }

        $sql = "
            SELECT o.*, c.name AS customer_name
            FROM sms_outbox o
            LEFT JOIN customers c ON c.id = o.customer_id
            {$where}
            ORDER BY o.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOutboxCount(?string $status = null, ?string $phoneSearch = null): int
    {
        $where = " WHERE 1=1";
        $params = [];

        if ($status !== null && $status !== '' && $status !== 'all') {
            $where .= " AND o.status = ?";
            $params[] = $status;
        }

        if ($phoneSearch !== null && $phoneSearch !== '') {
            $where .= " AND (o.to_phone LIKE ? OR c.name LIKE ?)";
            $params[] = "%{$phoneSearch}%";
            $params[] = "%{$phoneSearch}%";
        }

        $sql = "
            SELECT COUNT(*)
            FROM sms_outbox o
            LEFT JOIN customers c ON c.id = o.customer_id
            {$where}
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get all templates.
     * @return array<int, array<string, mixed>>
     */
    public function getTemplates(): array
    {
        return $this->pdo->query("SELECT * FROM sms_templates ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update template body.
     */
    public function updateTemplate(string $code, string $body, bool $isActive = true): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE sms_templates SET body = ?, is_active = ? WHERE code = ?
        ");
        $stmt->execute([trim($body), $isActive ? 1 : 0, $code]);

        Audit::log('update', 'sms_templates', null, null, [
            'code' => $code,
            'body' => $body,
            'is_active' => $isActive,
        ]);
    }
}

