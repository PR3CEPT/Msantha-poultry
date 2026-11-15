<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\Config;
use App\Core\DB;
use App\Services\PayChangu\CurlPayChanguHttpClient;
use App\Services\PayChangu\PayChanguHttpClientInterface;
use App\Services\SMS\SmsService;
use DomainException;
use InvalidArgumentException;
use PDO;

class PayChanguService
{
    private PDO $pdo;
    private PayChanguHttpClientInterface $httpClient;
    private SmsService $smsService;
    private string $publicKey;
    private string $secretKey;
    private string $webhookSecret;
    private string $apiBase;
    private string $callbackUrl;
    private string $returnUrl;
    private string $mode;

    public function __construct(
        ?PDO $pdo = null,
        ?PayChanguHttpClientInterface $httpClient = null,
        ?SmsService $smsService = null
    ) {
        $this->pdo = $pdo ?? DB::pdo();
        $this->httpClient = $httpClient ?? new CurlPayChanguHttpClient();
        $this->smsService = $smsService ?? new SmsService($this->pdo);

        $this->publicKey = (string) Config::get('paychangu.public_key', $_ENV['PAYCHANGU_PUBLIC_KEY'] ?? 'PUB-2KRAb51ifq8JkLbljEGQOKQ7UH7eHMAb');
        if (empty($this->publicKey)) {
            $this->publicKey = 'PUB-2KRAb51ifq8JkLbljEGQOKQ7UH7eHMAb';
        }
        $this->secretKey = (string) Config::get('paychangu.secret_key', $_ENV['PAYCHANGU_SECRET_KEY'] ?? 'SEC-BO5EWL1sgbO0PZUAkdj6T36NHyhAWF2x');
        if (empty($this->secretKey) || $this->secretKey === 'sec-test-msantha') {
            $this->secretKey = 'SEC-BO5EWL1sgbO0PZUAkdj6T36NHyhAWF2x';
        }
        $this->webhookSecret = (string) Config::get('paychangu.webhook_secret', $_ENV['PAYCHANGU_WEBHOOK_SECRET'] ?? 'whsec-live-msantha');
        $this->apiBase = (string) Config::get('paychangu.api_base', 'https://api.paychangu.com');
        $this->callbackUrl = (string) Config::get('paychangu.callback_url', $_ENV['PAYCHANGU_CALLBACK_URL'] ?? '');
        $this->returnUrl = (string) Config::get('paychangu.return_url', $_ENV['PAYCHANGU_RETURN_URL'] ?? '');
        $this->mode = (string) Config::get('paychangu.mode', $_ENV['PAYCHANGU_MODE'] ?? 'live');
    }

    public function getHttpClient(): PayChanguHttpClientInterface
    {
        return $this->httpClient;
    }

    public function setHttpClient(PayChanguHttpClientInterface $client): void
    {
        $this->httpClient = $client;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function getWebhookSecret(): string
    {
        return $this->webhookSecret;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Initiate an online payment via PayChangu API.
     *
     * @param array<string, mixed> $customerDetails [first_name, last_name, email, phone]
     * @param array<string, mixed> $customization [title, description]
     * @return array{tx_ref: string, checkout_url: string, payment_id: int}
     */
    public function initiatePayment(
        int $amount,
        string $purpose,
        ?int $purposeId = null,
        ?int $customerId = null,
        array $customerDetails = [],
        array $customization = []
    ): array {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Payment amount must be greater than zero.");
        }

        // Generate unique reference (MSP-YYYYMMDD-<random>)
        $txRef = 'MSP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

        // Load customer data if customerId is given and details not provided
        if ($customerId !== null && empty($customerDetails['first_name'])) {
            $stmt = $this->pdo->prepare("SELECT name, phone FROM customers WHERE id = ? LIMIT 1");
            $stmt->execute([$customerId]);
            $c = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($c) {
                $nameParts = explode(' ', trim((string)$c['name']), 2);
                $customerDetails['first_name'] = $nameParts[0] ?? 'Valued';
                $customerDetails['last_name'] = $nameParts[1] ?? 'Customer';
                $customerDetails['phone'] = $customerDetails['phone'] ?? $c['phone'];
            }
        }

        $firstName = trim((string) ($customerDetails['first_name'] ?? 'Customer'));
        $lastName = trim((string) ($customerDetails['last_name'] ?? ''));
        $email = trim((string) ($customerDetails['email'] ?? 'payments@msanthapoultry.mw'));

        if ($purpose === 'incubation') {
            $purpose = 'incubation_job';
        }

        // Insert pending row into payments
        $stmt = $this->pdo->prepare("
            INSERT INTO payments (
                tx_ref, purpose, purpose_id, customer_id, amount, currency, status, created_at
            ) VALUES (?, ?, ?, ?, ?, 'MWK', 'pending', NOW())
        ");
        $stmt->execute([
            $txRef,
            $purpose,
            $purposeId,
            $customerId,
            $amount,
        ]);
        $paymentId = (int) $this->pdo->lastInsertId();

        // Build API payload according to PayChangu docs
        $payload = [
            'public_key' => $this->publicKey,
            'amount' => $amount,
            'currency' => 'MWK',
            'tx_ref' => $txRef,
            'callback_url' => $this->callbackUrl ?: 'https://msanthapoultry.mw/webhooks/paychangu',
            'return_url' => ($this->returnUrl ?: 'https://msanthapoultry.mw/payments/return') . '?tx_ref=' . urlencode($txRef),
            'first_name' => $firstName,
            'last_name' => $lastName ?: $firstName,
            'email' => $email,
            'customization' => [
                'title' => $customization['title'] ?? 'Msantha Poultry Farm',
                'description' => $customization['description'] ?? ("Payment for " . str_replace('_', ' ', $purpose) . ($purposeId ? " #{$purposeId}" : "")),
            ],
            'meta' => [
                'purpose' => $purpose,
                'purpose_id' => $purposeId,
                'customer_id' => $customerId,
            ],
        ];

        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
        ];

        $resp = $this->httpClient->post($this->apiBase . '/payment', $headers, $payload);
        $body = $resp['body'];

        $checkoutUrl = null;
        if (is_array($body) && isset($body['data']['checkout_url'])) {
            $checkoutUrl = (string) $body['data']['checkout_url'];
        } elseif (is_array($body) && isset($body['checkout_url'])) {
            $checkoutUrl = (string) $body['checkout_url'];
        } else {
            // Fallback for mock or direct checkout link
            $checkoutUrl = 'https://checkout.paychangu.com/pay/' . $txRef;
        }

        // Update checkout_url in DB
        $this->pdo->prepare("UPDATE payments SET checkout_url = ? WHERE id = ?")->execute([$checkoutUrl, $paymentId]);

        Audit::log('create', 'payments', $paymentId, null, [
            'tx_ref' => $txRef,
            'amount' => $amount,
            'currency' => 'MWK',
            'purpose' => $purpose,
            'purpose_id' => $purposeId,
        ]);

        return [
            'tx_ref' => $txRef,
            'checkout_url' => $checkoutUrl,
            'payment_id' => $paymentId,
        ];
    }

    /**
     * Verify payment with PayChangu API and fulfill upon success.
     * Accepts ONLY if: tx_ref matches, status == 'success', currency == 'MWK', amount >= expected.
     *
     * @return array{verified: bool, payment: ?array<string, mixed>, error: ?string}
     */
    public function verifyPayment(string $txRef): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE tx_ref = ? LIMIT 1");
        $stmt->execute([$txRef]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            return [
                'verified' => false,
                'payment' => null,
                'error' => "Transaction reference not found in database: {$txRef}",
            ];
        }

        // If already succeeded, return payment
        if ($payment['status'] === 'success') {
            return [
                'verified' => true,
                'payment' => $payment,
                'error' => null,
            ];
        }

        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
        ];

        $resp = $this->httpClient->get($this->apiBase . '/verify-payment/' . urlencode($txRef), $headers);
        $body = $resp['body'];

        if ($resp['status_code'] < 200 || $resp['status_code'] >= 300 || !is_array($body)) {
            return [
                'verified' => false,
                'payment' => $payment,
                'error' => "PayChangu verify endpoint returned HTTP " . $resp['status_code'],
            ];
        }

        $data = $body['data'] ?? $body;
        $receivedTxRef = (string) ($data['tx_ref'] ?? '');
        $status = strtolower((string) ($data['status'] ?? ''));
        $currency = strtoupper((string) ($data['currency'] ?? ''));
        $receivedAmount = (int) ($data['amount'] ?? 0);
        $expectedAmount = (int) $payment['amount'];

        // Strict validation rules
        if ($receivedTxRef !== $txRef) {
            return [
                'verified' => false,
                'payment' => $payment,
                'error' => "Transaction reference mismatch: expected {$txRef}, got {$receivedTxRef}",
            ];
        }

        if ($status !== 'success') {
            if (in_array($status, ['failed', 'cancelled'], true)) {
                $this->pdo->prepare("UPDATE payments SET status = ?, raw_verify = ? WHERE id = ?")->execute([
                    $status,
                    json_encode($body, JSON_UNESCAPED_UNICODE),
                    (int) $payment['id'],
                ]);
            }
            return [
                'verified' => false,
                'payment' => $payment,
                'error' => "Payment status is not success: '{$status}'",
            ];
        }

        if ($currency !== 'MWK') {
            return [
                'verified' => false,
                'payment' => $payment,
                'error' => "Currency mismatch: expected MWK, got {$currency}",
            ];
        }

        if ($receivedAmount < $expectedAmount) {
            return [
                'verified' => false,
                'payment' => $payment,
                'error' => "Amount underpaid: expected MK {$expectedAmount}, got MK {$receivedAmount}",
            ];
        }

        // All checks passed! Mark success and fulfill
        $chargeId = (string) ($data['charge_id'] ?? $data['id'] ?? null);
        $channel = (string) ($data['channel'] ?? $data['payment_type'] ?? 'mobile_money');
        $mobileNumber = (string) ($data['mobile_number'] ?? $data['phone'] ?? null);

        $this->pdo->prepare("
            UPDATE payments SET
                status = 'success',
                paychangu_charge_id = ?,
                channel = ?,
                mobile_number = ?,
                raw_verify = ?,
                verified_at = NOW()
            WHERE id = ?
        ")->execute([
            $chargeId,
            $channel,
            $mobileNumber,
            json_encode($body, JSON_UNESCAPED_UNICODE),
            (int) $payment['id'],
        ]);

        $payment['status'] = 'success';
        $payment['paychangu_charge_id'] = $chargeId;
        $payment['channel'] = $channel;
        $payment['mobile_number'] = $mobileNumber;

        // Fulfill once
        $this->fulfillPayment($payment);

        return [
            'verified' => true,
            'payment' => $payment,
            'error' => null,
        ];
    }

    /**
     * Webhook receiver: validates HMAC signature, ensures idempotency, and re-verifies.
     *
     * @return array{valid: bool, duplicate: bool, verified: bool, error: ?string}
     */
    public function handleWebhook(string $rawBody, ?string $signatureHeader): array
    {
        // 1. Signature check
        if (empty($signatureHeader) || empty($this->webhookSecret)) {
            return [
                'valid' => false,
                'duplicate' => false,
                'verified' => false,
                'error' => 'Missing signature or webhook secret not configured',
            ];
        }

        $computedSignature = hash_hmac('sha256', $rawBody, $this->webhookSecret);
        if (!hash_equals($computedSignature, $signatureHeader)) {
            return [
                'valid' => false,
                'duplicate' => false,
                'verified' => false,
                'error' => 'Invalid HMAC-SHA256 signature',
            ];
        }

        $json = json_decode($rawBody, true);
        if (!is_array($json)) {
            return [
                'valid' => false,
                'duplicate' => false,
                'verified' => false,
                'error' => 'Malformed JSON body',
            ];
        }

        $data = $json['data'] ?? $json;
        $txRef = (string) ($data['tx_ref'] ?? '');
        $eventKey = (string) ($json['event_id'] ?? $txRef ?: ('evt_' . md5($rawBody)));

        // 2. Idempotency storage in webhook_events
        $chk = $this->pdo->prepare("SELECT id, processed FROM webhook_events WHERE provider = 'paychangu' AND event_key = ? LIMIT 1");
        $chk->execute([$eventKey]);
        $existingEvent = $chk->fetch(PDO::FETCH_ASSOC);

        if ($existingEvent) {
            return [
                'valid' => true,
                'duplicate' => true,
                'verified' => (bool) $existingEvent['processed'],
                'error' => null,
            ];
        }

        // Store new webhook event
        $ins = $this->pdo->prepare("
            INSERT INTO webhook_events (provider, event_key, payload, signature_valid, processed, received_at)
            VALUES ('paychangu', ?, ?, 1, 0, NOW())
        ");
        $ins->execute([$eventKey, json_encode($json, JSON_UNESCAPED_UNICODE)]);
        $webhookEventId = (int) $this->pdo->lastInsertId();

        // 3. Re-verify via verify endpoint before marking paid (as required by blueprint section 5)
        $verified = false;
        if ($txRef !== '') {
            $verifyResult = $this->verifyPayment($txRef);
            $verified = $verifyResult['verified'];

            if ($verified) {
                // Update raw_webhook on payment
                $this->pdo->prepare("UPDATE payments SET raw_webhook = ? WHERE tx_ref = ?")->execute([
                    json_encode($json, JSON_UNESCAPED_UNICODE),
                    $txRef,
                ]);
            }
        }

        // Mark webhook event processed
        $this->pdo->prepare("UPDATE webhook_events SET processed = 1 WHERE id = ?")->execute([$webhookEventId]);

        return [
            'valid' => true,
            'duplicate' => false,
            'verified' => $verified,
            'error' => null,
        ];
    }

    /**
     * Fulfill payment (run once only per successful payment).
     * Creates incubation_payments or sale payment, executes 25/15/30/20/10 allocation,
     * reflects in POULTRY cash book, and queues receipt SMS.
     *
     * @param array<string, mixed> $payment
     */
    public function fulfillPayment(array $payment): void
    {
        $paymentId = (int) $payment['id'];
        $amount = (int) $payment['amount'];
        $purpose = (string) $payment['purpose'];
        $purposeId = !empty($payment['purpose_id']) ? (int) $payment['purpose_id'] : null;
        $customerId = !empty($payment['customer_id']) ? (int) $payment['customer_id'] : null;
        $txRef = (string) $payment['tx_ref'];

        // -------------------------------------------------------------
        // Fulfillment Case 1: Incubation Job
        // -------------------------------------------------------------
        if (in_array($purpose, ['incubation_job', 'incubation'], true) && $purposeId !== null) {
            // Check if already fulfilled
            $chk = $this->pdo->prepare("SELECT id FROM incubation_payments WHERE payment_id = ? LIMIT 1");
            $chk->execute([$paymentId]);
            if ($chk->fetchColumn()) {
                return; // Already fulfilled!
            }

            // Create incubation payment row
            $stmt = $this->pdo->prepare("
                INSERT INTO incubation_payments (job_id, paid_on, amount, method, payment_id)
                VALUES (?, CURDATE(), ?, 'paychangu', ?)
            ");
            $stmt->execute([$purposeId, $amount, $paymentId]);
            $incPaymentId = (int) $this->pdo->lastInsertId();

            // Sync job amount_paid column
            $this->pdo->prepare("
                UPDATE incubation_jobs
                SET amount_paid = (SELECT COALESCE(SUM(amount), 0) FROM incubation_payments WHERE job_id = ?)
                WHERE id = ?
            ")->execute([$purposeId, $purposeId]);

            // Run statutory allocation: 25% electricity, 15% water, 30% labour, 20% telephone, 10% + remainder profit
            $elec = (int) floor($amount * 0.25);
            $water = (int) floor($amount * 0.15);
            $labour = (int) floor($amount * 0.30);
            $phone = (int) floor($amount * 0.20);
            $profit = $amount - ($elec + $water + $labour + $phone);

            $allocStmt = $this->pdo->prepare("
                INSERT INTO incubation_allocations (payment_id, category, amount)
                VALUES 
                    (?, 'electricity', ?),
                    (?, 'water', ?),
                    (?, 'labour', ?),
                    (?, 'telephone', ?),
                    (?, 'profit', ?)
            ");
            $allocStmt->execute([
                $incPaymentId, $elec,
                $incPaymentId, $water,
                $incPaymentId, $labour,
                $incPaymentId, $phone,
                $incPaymentId, $profit,
            ]);

            // Auto-reflect in POULTRY cash book
            $this->pdo->prepare("
                INSERT INTO cash_entries (
                    business_unit_id, entry_date, date_missing, description, receipt, expenditure, entry_kind, legacy_ref
                ) VALUES (1, CURDATE(), 0, ?, ?, 0, 'transaction', ?)
            ")->execute([
                "PayChangu online payment ({$txRef}) - Batch #{$purposeId}",
                $amount,
                "paychangu:tx_{$txRef}",
            ]);

            // Fetch job info for balance calculation and SMS
            $jobStmt = $this->pdo->prepare("
                SELECT j.*, c.name AS customer_name, c.phone AS customer_phone,
                       (CAST(j.cost AS SIGNED) - CAST(COALESCE((SELECT SUM(amount) FROM incubation_payments WHERE job_id = j.id), 0) AS SIGNED)) AS balance
                FROM incubation_jobs j
                LEFT JOIN customers c ON c.id = j.customer_id
                WHERE j.id = ?
                LIMIT 1
            ");
            $jobStmt->execute([$purposeId]);
            $job = $jobStmt->fetch(PDO::FETCH_ASSOC);

            if ($job && !empty($job['customer_phone'])) {
                $balance = (int) ($job['balance'] ?? 0);
                $this->smsService->queue(
                    'PAYMENT_RECEIPT',
                    (string) $job['customer_phone'],
                    [
                        'amount' => number_format($amount),
                        'date' => date('d-M-Y'),
                        'balance' => number_format(max(0, $balance)),
                        'customer_name' => $job['customer_name'] ?? 'Customer',
                    ],
                    "PAYMENT_RECEIPT:paychangu-{$paymentId}",
                    !empty($job['customer_id']) ? (int) $job['customer_id'] : null,
                    null,
                    'payments',
                    $paymentId
                );
            }

            Audit::log('fulfill_payment', 'incubation_payments', $incPaymentId, null, [
                'payment_id' => $paymentId,
                'tx_ref' => $txRef,
                'amount' => $amount,
                'job_id' => $purposeId,
                'allocations' => [
                    'electricity' => $elec,
                    'water' => $water,
                    'labour' => $labour,
                    'telephone' => $phone,
                    'profit' => $profit,
                ],
            ]);
        }

        // -------------------------------------------------------------
        // Fulfillment Case 2: Commercial Sale
        // -------------------------------------------------------------
        if ($purpose === 'sale' && $purposeId !== null) {
            $this->pdo->prepare("
                UPDATE sales SET is_credit = 0 WHERE id = ?
            ")->execute([$purposeId]);

            // Auto-reflect in POULTRY cash book
            $this->pdo->prepare("
                INSERT INTO cash_entries (
                    business_unit_id, entry_date, date_missing, description, receipt, expenditure, entry_kind, legacy_ref
                ) VALUES (1, CURDATE(), 0, ?, ?, 0, 'transaction', ?)
            ")->execute([
                "PayChangu online payment ({$txRef}) - Sale #{$purposeId}",
                $amount,
                "paychangu:sale_{$purposeId}",
            ]);

            Audit::log('fulfill_payment', 'sales', $purposeId, null, [
                'payment_id' => $paymentId,
                'tx_ref' => $txRef,
                'amount' => $amount,
                'sale_id' => $purposeId,
            ]);
        }
    }

    /**
     * Get payments list with filtering.
     * @return array<int, array<string, mixed>>
     */
    public function getPayments(?string $status = null, ?string $search = null, int $limit = 50, int $offset = 0): array
    {
        $where = " WHERE 1=1";
        $params = [];

        if ($status !== null && $status !== '' && $status !== 'all') {
            $where .= " AND p.status = ?";
            $params[] = $status;
        }

        if ($search !== null && $search !== '') {
            $where .= " AND (p.tx_ref LIKE ? OR c.name LIKE ? OR p.paychangu_charge_id LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $sql = "
            SELECT p.*, c.name AS customer_name, c.phone AS customer_phone
            FROM payments p
            LEFT JOIN customers c ON c.id = p.customer_id
            {$where}
            ORDER BY p.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaymentsCount(?string $status = null, ?string $search = null): int
    {
        $where = " WHERE 1=1";
        $params = [];

        if ($status !== null && $status !== '' && $status !== 'all') {
            $where .= " AND p.status = ?";
            $params[] = $status;
        }

        if ($search !== null && $search !== '') {
            $where .= " AND (p.tx_ref LIKE ? OR c.name LIKE ? OR p.paychangu_charge_id LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $sql = "
            SELECT COUNT(*)
            FROM payments p
            LEFT JOIN customers c ON c.id = p.customer_id
            {$where}
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}

