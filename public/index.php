<?php

declare(strict_types=1);

use App\Controllers\Admin\AuditController;
use App\Controllers\Admin\BackupController;
use App\Controllers\Admin\DataReviewController;
use App\Controllers\Admin\ImportController;
use App\Controllers\Admin\InquiryController;
use App\Controllers\Admin\SmsController;
use App\Controllers\Admin\UserController;
use App\Controllers\Api\PublicController;
use App\Controllers\Api\SyncController;
use App\Controllers\AuthController;
use App\Controllers\CustomerController;
use App\Controllers\EggController;
use App\Controllers\FeedDrugController;
use App\Controllers\FinanceController;
use App\Controllers\FlockController;
use App\Controllers\HealthController;
use App\Controllers\HomeController;
use App\Controllers\IncubationController;
use App\Controllers\PaymentController;
use App\Controllers\ProfileController;
use App\Controllers\ReportController;
use App\Controllers\SaleController;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\View;
use App\Middleware\CsrfMiddleware;
use App\Middleware\SecurityHeadersMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

// Define Application Base Path
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Load Configuration and Environment
Config::load(dirname(__DIR__));

// Timezone setup
$timezone = (string) Config::get('app.timezone', 'Africa/Blantyre');
date_default_timezone_set($timezone);

// Error Handling setup
$debug = (bool) Config::get('app.debug', false);
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Session configuration (Hardened: HttpOnly, SameSite=Lax)
$sessionPath = dirname(__DIR__) . '/storage/cache/sessions';
if (is_dir($sessionPath) && is_writable($sessionPath)) {
    session_save_path($sessionPath);
}

if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $cookieParams = [
        'lifetime' => 86400 * 7,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    session_set_cookie_params($cookieParams);
    session_start();
}

$request = Request::capture();

// Single Active Session tracker & idle timeout check
Auth::touchSessionActivity();

// Forced Password Change Policy (A1)
if (Auth::check() && Auth::mustChangePassword()) {
    $currentUri = $request->uri();
    if (!in_array($currentUri, ['/force-password-change', '/logout'], true)) {
        Response::redirect(url('/force-password-change'))->send();
        exit(0);
    }
}

$router = new Router();

// Global Security Headers Middleware
$router->addMiddleware(SecurityHeadersMiddleware::class);

// Global CSRF Middleware (verifies every POST/PUT/DELETE request)
$router->addMiddleware(CsrfMiddleware::class);

// --- Public & Health Routes ---
$router->get('/health', [HealthController::class, 'check']);

// --- Authentication Routes ---
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'sendResetCode']);
$router->get('/verify-reset-code', [AuthController::class, 'showVerifyResetCode']);
$router->post('/verify-reset-code', [AuthController::class, 'verifyResetCode']);
$router->get('/reset-password', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);

$router->get('/force-password-change', [AuthController::class, 'showForcePasswordChange']);
$router->post('/force-password-change', [AuthController::class, 'forcePasswordChange']);

$router->get('/change-password', [AuthController::class, 'showChangePassword']);
$router->post('/change-password', [AuthController::class, 'changePassword']);

// --- User Profile Routes ---
$router->get('/profile', [ProfileController::class, 'index']);
$router->post('/profile', [ProfileController::class, 'update']);
$router->post('/profile/password', [ProfileController::class, 'changePassword']);
$router->post('/profile/request-username-change', [ProfileController::class, 'requestUsernameChange']);

// --- Admin Routes (Owner & Isaac with users.change_roles) ---
$router->get('/admin/users', [UserController::class, 'index']);
$router->get('/admin/users/create', [UserController::class, 'create']);
$router->post('/admin/users', [UserController::class, 'store']);
$router->get('/admin/users/{id}/edit', [UserController::class, 'edit']);
$router->post('/admin/users/{id}', [UserController::class, 'update']);
$router->post('/admin/users/{id}/toggle-active', [UserController::class, 'toggleActive']);
$router->post('/admin/users/{id}/force-logout', [UserController::class, 'forceLogout']);
$router->post('/admin/users/username-request/{id}/approve', [UserController::class, 'approveUsernameRequest']);
$router->post('/admin/users/username-request/{id}/reject', [UserController::class, 'rejectUsernameRequest']);

// --- Admin Audit Routes (Owner & Manager) ---
$router->get('/admin/audit', [AuditController::class, 'index']);
$router->get('/admin/audit/export', [AuditController::class, 'exportCsv']);

// --- Admin Import Routes (Owner & Manager) ---
$router->get('/admin/import', [ImportController::class, 'index']);
$router->post('/admin/import/run', [ImportController::class, 'run']);
$router->get('/admin/import/template/{type}', [ImportController::class, 'downloadTemplate']);

// --- Admin Data Review Routes (Owner & Manager) ---
$router->get('/admin/data-review', [DataReviewController::class, 'index']);
$router->post('/admin/data-review/resolve', [DataReviewController::class, 'resolveDate']);

// --- Admin Backup Routes (Owner Only) ---
$router->get('/admin/backups', [BackupController::class, 'index']);
$router->post('/admin/backups/create', [BackupController::class, 'create']);
$router->get('/admin/backups/{filename}/download', [BackupController::class, 'download']);

// --- Dashboard & Operational Module Routes with Role / Permission Enforcement ---
$router->get('/', [HomeController::class, 'index']);
$router->get('/landing', [HomeController::class, 'landing']);
$router->get('/dashboard', [HomeController::class, 'index']);

// Customers Routes
$router->get('/customers', [CustomerController::class, 'index']);
$router->get('/customers/create', [CustomerController::class, 'create']);
$router->post('/customers', [CustomerController::class, 'store']);
$router->get('/customers/{id}', [CustomerController::class, 'show']);
$router->get('/customers/{id}/edit', [CustomerController::class, 'edit']);
$router->post('/customers/{id}', [CustomerController::class, 'update']);
$router->post('/customers/merge', [CustomerController::class, 'merge']);

// Incubation Routes
$router->get('/incubation-jobs', [IncubationController::class, 'index']);
$router->get('/incubation/receive', [IncubationController::class, 'receive']);
$router->post('/incubation-jobs', [IncubationController::class, 'store']);
$router->get('/incubation-jobs/{id}', [IncubationController::class, 'show']);
$router->post('/incubation-jobs/{id}/hatch', [IncubationController::class, 'recordHatch']);
$router->post('/incubation-jobs/{id}/payment', [IncubationController::class, 'addPayment']);
$router->get('/incubation-jobs/{id}/receipt', [IncubationController::class, 'receipt']);
$router->get('/incubation-jobs/{id}/certificate', [IncubationController::class, 'certificate']);

// Flocks Routes
$router->get('/flocks', [FlockController::class, 'index']);
$router->get('/flocks/create', [FlockController::class, 'create']);
$router->post('/flocks', [FlockController::class, 'store']);
$router->get('/flocks/quick-entry', [FlockController::class, 'quickEntry']);
$router->post('/flocks/quick-entry', [FlockController::class, 'saveQuickEntry']);
$router->get('/flocks/price-ladder', [FlockController::class, 'priceLadder']);
$router->post('/flocks/price-ladder', [FlockController::class, 'savePriceLadder']);
$router->get('/flocks/{id}', [FlockController::class, 'show']);
$router->get('/flocks/{id}/edit', [FlockController::class, 'edit']);
$router->post('/flocks/{id}', [FlockController::class, 'update']);
$router->post('/flocks/{id}/close', [FlockController::class, 'close']);

// Egg Production Routes
$router->get('/eggs', [EggController::class, 'index']);
$router->get('/eggs/create', [EggController::class, 'create']);
$router->post('/eggs', [EggController::class, 'store']);

// Feed & Drugs Routes
$router->get('/feed-drugs', [FeedDrugController::class, 'index']);
$router->post('/feed-drugs/feed', [FeedDrugController::class, 'storeFeed']);
$router->post('/feed-drugs/drug', [FeedDrugController::class, 'storeDrug']);

// Sales Routes
$router->get('/sales', [SaleController::class, 'index']);
$router->get('/sales/create', [SaleController::class, 'create']);
$router->post('/sales', [SaleController::class, 'store']);
$router->get('/sales/{id}/receipt', [SaleController::class, 'receipt']);

// Finance Routes
$router->get('/finance', [FinanceController::class, 'index']);
$router->get('/finance/receipt', [FinanceController::class, 'receipt']);
$router->get('/finance/{id}/receipt', [FinanceController::class, 'receipt']);
$router->post('/finance/entries', [FinanceController::class, 'storeEntry']);
$router->post('/finance/lock-month', [FinanceController::class, 'lockMonth']);
$router->post('/finance/unlock-month', [FinanceController::class, 'unlockMonth']);
$router->post('/finance/bank-movements', [FinanceController::class, 'storeBankMovement']);
$router->get('/finance/profit-and-loss', [FinanceController::class, 'profitAndLoss']);
$router->get('/finance/pnl', [FinanceController::class, 'profitAndLoss']);
$router->get('/finance/export-csv', [FinanceController::class, 'exportCsv']);
$router->get('/finance/reconcile', [FinanceController::class, 'reconcile']);
$router->get('/finance/reconciliation', [FinanceController::class, 'reconcile']);

// SMS Routes
$router->get('/admin/sms', [SmsController::class, 'index']);
$router->post('/admin/sms/retry', [SmsController::class, 'retry']);
$router->post('/admin/sms/bulk', [SmsController::class, 'bulkSend']);
$router->get('/admin/settings/sms', [SmsController::class, 'settings']);
$router->post('/admin/settings/sms', [SmsController::class, 'updateSettings']);
$router->post('/admin/settings/sms/test', [SmsController::class, 'testSms']);
$router->get('/admin/sms-templates', [SmsController::class, 'templates']);
$router->post('/admin/sms-templates', [SmsController::class, 'updateTemplate']);
$router->post('/api/sms/delivery-report', [SmsController::class, 'deliveryReport']);
$router->post('/api/sync', [SyncController::class, 'sync']);

// PayChangu Payment Routes
$router->post('/payments/initiate', [PaymentController::class, 'initiate']);
$router->post('/payments/send-link-sms', [PaymentController::class, 'sendPayLinkSms']);
$router->get('/payments/return', [PaymentController::class, 'returnUrl']);
$router->post('/webhooks/paychangu', [PaymentController::class, 'webhook']);
$router->get('/admin/payments', [PaymentController::class, 'adminIndex']);
$router->post('/admin/payments/reverify', [PaymentController::class, 'reverify']);

// Notification API Routes
$router->post('/api/notifications/mark-seen', [\App\Controllers\Api\NotificationController::class, 'markSeen']);
$router->post('/api/notifications/dismiss', [\App\Controllers\Api\NotificationController::class, 'dismiss']);

// Reports Routes
$router->get('/reports', [ReportController::class, 'index']);
$router->get('/reports/mortality', [ReportController::class, 'mortality']);
$router->get('/reports/hatch', [ReportController::class, 'hatch']);
$router->get('/reports/eggs', [ReportController::class, 'eggs']);
$router->get('/reports/aged-debtors', [ReportController::class, 'agedDebtors']);
$router->get('/reports/sales-by-item', [ReportController::class, 'salesByItem']);
$router->get('/reports/incubation-allocation', [ReportController::class, 'incubationAllocation']);
$router->get('/reports/feed-efficiency', [ReportController::class, 'feedEfficiency']);

// Public Website APIs (Incubation Tracking & Contact Inquiries)
$router->post('/api/public/track-incubation', [PublicController::class, 'trackIncubation']);
$router->post('/api/public/contact-inquiry', [PublicController::class, 'submitInquiry']);

// Admin Web Inquiries (Shared Manager Queue)
$router->get('/admin/inquiries', [InquiryController::class, 'index']);
$router->post('/admin/inquiries/{id}/attend', [InquiryController::class, 'attend']);

$router->get('/more', [HomeController::class, 'more']);

try {
    $response = $router->dispatch($request);
} catch (Throwable $e) {
    Logger::error($e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    if ($debug) {
        $response = Response::html(
            '<h1>500 System Error</h1><p>' . e($e->getMessage()) . '</p><pre>' . e($e->getTraceAsString()) . '</pre>',
            500
        );
    } else {
        try {
            $response = View::render('errors/500', ['pageTitle' => 'Error 500'], null);
        } catch (\Throwable) {
            $response = Response::html('<h1>500 System Error</h1><p>An unexpected error occurred.</p>', 500);
        }
        $response->setStatusCode(500);
    }
}

$response->send();
