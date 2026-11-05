<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\SMS\SmsProviderFactory;
use App\Services\SMS\SmsService;
use PDO;

class SmsController
{
    private SmsService $smsService;

    public function __construct(?SmsService $smsService = null)
    {
        $this->smsService = $smsService ?? new SmsService();
    }

    /**
     * SMS Centre: Outbox view, filters, retry button, bulk modal.
     */
    public function index(Request $request): Response
    {
        Auth::requirePermission('sms', 'view');
        Audit::logView('sms_centre');

        $status = trim((string) $request->query('status', 'all'));
        $search = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;

        $outbox = $this->smsService->getOutbox($status, $search, $perPage, $offset);
        $totalRows = $this->smsService->getOutboxCount($status, $search);
        $totalPages = max(1, (int) ceil($totalRows / $perPage));

        $pdo = DB::pdo();
        $stats = [
            'queued' => (int) $pdo->query("SELECT COUNT(*) FROM sms_outbox WHERE status = 'queued'")->fetchColumn(),
            'sent' => (int) $pdo->query("SELECT COUNT(*) FROM sms_outbox WHERE status IN ('sent', 'delivered')")->fetchColumn(),
            'failed' => (int) $pdo->query("SELECT COUNT(*) FROM sms_outbox WHERE status = 'failed'")->fetchColumn(),
        ];

        // Fetch opt-in customers with phones for bulk SMS modal
        $customers = $pdo->query("
            SELECT id, name, phone 
            FROM customers 
            WHERE is_internal = 0 AND sms_opt_in = 1 AND phone IS NOT NULL AND phone != '' 
            ORDER BY name ASC 
            LIMIT 200
        ")->fetchAll(PDO::FETCH_ASSOC);

        $costPerSegment = (int) ($pdo->query("SELECT svalue FROM settings WHERE skey = 'sms_cost_per_segment_mwk' LIMIT 1")->fetchColumn() ?: 25);

        return View::render('admin/sms/index', [
            'pageTitle' => 'SMS Notification Centre',
            'activeNav' => 'admin_sms',
            'outbox' => $outbox,
            'stats' => $stats,
            'customers' => $customers,
            'costPerSegment' => $costPerSegment,
            'status' => $status,
            'search' => $search,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows,
        ]);
    }

    /**
     * Retry a failed or stuck message.
     */
    public function retry(Request $request): Response
    {
        Auth::requirePermission('sms', 'update');

        $id = (int) $request->input('id');
        if ($id > 0) {
            $this->smsService->retry($id);
            $_SESSION['flash_success'] = "SMS #{$id} reset to queued for immediate retry.";
        }

        return Response::redirect(url('/admin/sms'));
    }

    /**
     * Send bulk SMS to selected customers.
     */
    public function bulkSend(Request $request): Response
    {
        Auth::requirePermission('sms', 'create');

        $customerIds = (array) $request->input('customer_ids', []);
        $message = trim((string) $request->input('message', ''));
        $userId = Auth::id();

        if (empty($customerIds) || $message === '') {
            $_SESSION['flash_error'] = "Please select at least one customer and provide a message.";
            return Response::redirect(url('/admin/sms'));
        }

        $res = $this->smsService->bulkSend($customerIds, $message, $userId);

        $_SESSION['flash_success'] = "Bulk SMS queued: {$res['queued']} message(s) queued ({$res['segments']} segment(s) each). {$res['suppressed']} suppressed/skipped.";
        return Response::redirect(url('/admin/sms'));
    }

    /**
     * SMS Settings screen.
     */
    public function settings(Request $request): Response
    {
        Auth::requireRole('owner');
        Audit::logView('sms_settings');

        $pdo = DB::pdo();
        $settingsRows = $pdo->query("SELECT skey, svalue FROM settings WHERE skey LIKE 'sms_%' OR skey LIKE 'africastalking_%'")->fetchAll(PDO::FETCH_ASSOC);
        $settings = [];
        foreach ($settingsRows as $r) {
            $settings[$r['skey']] = $r['svalue'];
        }

        return View::render('admin/sms/settings', [
            'pageTitle' => 'SMS Gateway Settings',
            'activeNav' => 'admin_settings',
            'settings' => $settings,
        ]);
    }

    /**
     * Update SMS settings with secure handling (no plaintext secrets printed).
     */
    public function updateSettings(Request $request): Response
    {
        Auth::requireRole('owner');
        $pdo = DB::pdo();

        $allowedKeys = [
            'sms_provider',
            'sms_sender_id',
            'africastalking_username',
            'africastalking_api_key',
            'africastalking_sandbox',
            'sms_http_url',
            'sms_http_method',
            'sms_http_headers',
            'sms_http_body_template',
            'sms_management_alert_phone',
            'sms_cost_per_segment_mwk',
        ];

        foreach ($allowedKeys as $key) {
            $val = $request->input($key);
            if ($val !== null) {
                // If API key is masked '******', don't overwrite with asterisks
                if ($key === 'africastalking_api_key' && str_starts_with((string)$val, '****')) {
                    continue;
                }

                $stmt = $pdo->prepare("
                    INSERT INTO settings (skey, svalue, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE svalue = VALUES(svalue), updated_at = NOW()
                ");
                $stmt->execute([$key, trim((string) $val)]);
            }
        }

        Audit::log('update', 'settings', null, null, [
            'module' => 'sms_gateway_settings',
            'updated_by' => Auth::id(),
        ]);

        $_SESSION['flash_success'] = "SMS gateway settings saved successfully.";
        return Response::redirect(url('/admin/settings/sms'));
    }

    /**
     * Send test SMS.
     */
    public function testSms(Request $request): Response
    {
        Auth::requireRole('owner');

        $testPhone = trim((string) $request->input('test_phone', ''));
        if (empty($testPhone)) {
            $_SESSION['flash_error'] = "Please provide a valid destination phone number.";
            return Response::redirect(url('/admin/settings/sms'));
        }

        $provider = SmsProviderFactory::create();
        $testMsg = "Msantha Poultry Test: SMS gateway is active and configured correctly. (" . date('H:i:s') . ")";
        $result = $provider->send($testPhone, $testMsg);

        if ($result['success']) {
            $_SESSION['flash_success'] = "Test SMS dispatched successfully! Provider message ID: " . htmlspecialchars($result['message_id'] ?? 'OK');
        } else {
            $_SESSION['flash_error'] = "Test SMS failed: " . htmlspecialchars($result['error'] ?? 'Unknown error');
        }

        return Response::redirect(url('/admin/settings/sms'));
    }

    /**
     * SMS Templates editor.
     */
    public function templates(Request $request): Response
    {
        Auth::requirePermission('sms', 'view');
        Audit::logView('sms_templates');

        $templates = $this->smsService->getTemplates();

        return View::render('admin/sms/templates', [
            'pageTitle' => 'SMS Notification Templates',
            'activeNav' => 'admin_sms',
            'templates' => $templates,
        ]);
    }

    /**
     * Update template body.
     */
    public function updateTemplate(Request $request): Response
    {
        Auth::requirePermission('sms', 'update');

        $code = trim((string) $request->input('code'));
        $body = trim((string) $request->input('body'));
        $isActive = (bool) $request->input('is_active', 1);

        if ($code !== '' && $body !== '') {
            $this->smsService->updateTemplate($code, $body, $isActive);
            $_SESSION['flash_success'] = "Template [{$code}] updated successfully.";
        }

        return Response::redirect(url('/admin/sms-templates'));
    }

    /**
     * Delivery Report Webhook Endpoint (POST /api/sms/delivery-report).
     */
    public function deliveryReport(Request $request): Response
    {
        $pdo = DB::pdo();
        $body = (string) $request->input('body', '');
        $msgId = trim((string) ($request->input('id') ?? $request->input('messageId') ?? ''));
        $status = strtolower(trim((string) ($request->input('status') ?? '')));

        if ($msgId !== '') {
            $mappedStatus = match ($status) {
                'success', 'delivered', 'sent' => 'delivered',
                'failed', 'rejected' => 'failed',
                default => 'sent',
            };

            $stmt = $pdo->prepare("
                UPDATE sms_outbox SET status = ? WHERE provider_msg_id = ?
            ");
            $stmt->execute([$mappedStatus, $msgId]);
        }

        return Response::json(['status' => 'received', 'timestamp' => time()]);
    }
}

