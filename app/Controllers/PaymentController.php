<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Config;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\PayChanguService;
use App\Services\SMS\SmsService;

class PaymentController
{
    private PayChanguService $paychangu;
    private SmsService $smsService;

    public function __construct(
        ?PayChanguService $paychangu = null,
        ?SmsService $smsService = null
    ) {
        $this->paychangu = $paychangu ?? new PayChanguService();
        $this->smsService = $smsService ?? new SmsService();
    }

    /**
     * Initiate online payment and redirect to PayChangu checkout.
     */
    public function initiate(Request $request): Response
    {
        Auth::requirePermission('payments', 'create');

        $amount = (int) $request->input('amount', 0);
        $purpose = trim((string) $request->input('purpose', 'incubation_job'));
        $purposeId = !empty($request->input('purpose_id')) ? (int) $request->input('purpose_id') : null;
        $customerId = !empty($request->input('customer_id')) ? (int) $request->input('customer_id') : null;

        if ($amount <= 0) {
            $_SESSION['flash_error'] = "Invalid payment amount.";
            return Response::redirect(url('/incubation-jobs' . ($purposeId ? "/{$purposeId}" : "")));
        }

        try {
            $res = $this->paychangu->initiatePayment($amount, $purpose, $purposeId, $customerId);

            if ($request->header('Accept') === 'application/json' || $request->input('format') === 'json') {
                return Response::json([
                    'success' => true,
                    'tx_ref' => $res['tx_ref'],
                    'checkout_url' => $res['checkout_url'],
                ]);
            }

            return Response::redirect($res['checkout_url']);
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = "Failed to initialize online payment: " . $e->getMessage();
            return Response::redirect(url('/incubation-jobs' . ($purposeId ? "/{$purposeId}" : "")));
        }
    }

    /**
     * Send payment link via SMS to customer phone.
     */
    public function sendPayLinkSms(Request $request): Response
    {
        Auth::requirePermission('payments', 'create');

        $amount = (int) $request->input('amount', 0);
        $purpose = trim((string) $request->input('purpose', 'incubation_job'));
        $purposeId = !empty($request->input('purpose_id')) ? (int) $request->input('purpose_id') : null;
        $customerId = !empty($request->input('customer_id')) ? (int) $request->input('customer_id') : null;

        $pdo = DB::pdo();
        $custStmt = $pdo->prepare("SELECT name, phone FROM customers WHERE id = ? LIMIT 1");
        $custStmt->execute([$customerId]);
        $cust = $custStmt->fetch();

        if (!$cust || empty($cust['phone'])) {
            $_SESSION['flash_error'] = "Customer does not have a valid mobile phone number on file.";
            return Response::redirect(url('/incubation-jobs' . ($purposeId ? "/{$purposeId}" : "")));
        }

        try {
            $init = $this->paychangu->initiatePayment($amount, $purpose, $purposeId, $customerId);
            $payUrl = $init['checkout_url'];

            $outId = $this->smsService->queue(
                'BALANCE_REMINDER',
                (string) $cust['phone'],
                [
                    'balance' => number_format($amount),
                    'date' => date('d-M-Y'),
                    'pay_link' => $payUrl,
                    'customer_name' => $cust['name'],
                ],
                "PAY_LINK:tx-{$init['tx_ref']}",
                $customerId
            );

            if ($outId !== null) {
                $_SESSION['flash_success'] = "Pay link dispatched via SMS to {$cust['phone']}.";
            } else {
                $_SESSION['flash_error'] = "Customer is opted-out or internal farm account; SMS was suppressed.";
            }
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = "Could not send payment link: " . $e->getMessage();
        }

        return Response::redirect(url('/incubation-jobs' . ($purposeId ? "/{$purposeId}" : "")));
    }

    /**
     * Return page after PayChangu checkout redirect.
     * "never trust the redirect; show 'checking payment…' and poll verify."
     */
    public function returnUrl(Request $request): Response
    {
        $txRef = trim((string) $request->query('tx_ref', ''));
        $verifyResult = null;

        if ($txRef !== '') {
            $verifyResult = $this->paychangu->verifyPayment($txRef);
        }

        return View::render('payments/return', [
            'pageTitle' => 'Checking Payment Status',
            'activeNav' => 'payments',
            'txRef' => $txRef,
            'result' => $verifyResult,
        ]);
    }

    /**
     * Webhook endpoint: POST /webhooks/paychangu
     */
    public function webhook(Request $request): Response
    {
        $rawBody = file_get_contents('php://input') ?: '';
        $signature = $_SERVER['HTTP_SIGNATURE'] ?? $_SERVER['HTTP_X_PAYCHANGU_SIGNATURE'] ?? '';

        $res = $this->paychangu->handleWebhook($rawBody, $signature);

        if (!$res['valid']) {
            return Response::json(['error' => $res['error']], 400);
        }

        return Response::json(['status' => 'received', 'processed' => true], 200);
    }

    /**
     * Admin Payments Center.
     */
    public function adminIndex(Request $request): Response
    {
        Auth::requirePermission('payments', 'view');
        Audit::logView('admin_payments');

        $status = trim((string) $request->query('status', 'all'));
        $search = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;

        $payments = $this->paychangu->getPayments($status, $search, $perPage, $offset);
        $totalRows = $this->paychangu->getPaymentsCount($status, $search);
        $totalPages = max(1, (int) ceil($totalRows / $perPage));

        $pdo = DB::pdo();
        $stats = [
            'total_volume' => (int) $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'success'")->fetchColumn(),
            'success_count' => (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'success'")->fetchColumn(),
            'pending_count' => (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn(),
            'failed_count' => (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE status IN ('failed', 'cancelled')")->fetchColumn(),
        ];

        $mode = $this->paychangu->getMode();

        return View::render('admin/payments/index', [
            'pageTitle' => 'Online Payments (PayChangu)',
            'activeNav' => 'admin_payments',
            'payments' => $payments,
            'stats' => $stats,
            'mode' => $mode,
            'status' => $status,
            'search' => $search,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows,
        ]);
    }

    /**
     * Re-verify payment manually from Admin table.
     */
    public function reverify(Request $request): Response
    {
        Auth::requirePermission('payments', 'update');

        $txRef = trim((string) $request->input('tx_ref', ''));
        if ($txRef !== '') {
            $res = $this->paychangu->verifyPayment($txRef);
            if ($res['verified']) {
                $_SESSION['flash_success'] = "Payment {$txRef} verified successfully and credited!";
            } else {
                $_SESSION['flash_error'] = "Re-verification failed: " . ($res['error'] ?? 'Unknown status');
            }
        }

        return Response::redirect(url('/admin/payments'));
    }
}

