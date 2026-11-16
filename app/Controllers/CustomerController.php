<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Phone;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

class CustomerController
{
    public function index(Request $request): Response
    {
        Auth::requirePermission('customers', 'view');
        $pdo = DB::pdo();

        $search = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        // Overall stats
        $stats = [
            'total_customers' => 0,
            'internal_customers' => 0,
            'total_spent' => 0,
            'total_balance' => 0,
        ];

        $stats['total_customers'] = (int) $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        $stats['internal_customers'] = (int) $pdo->query("SELECT COUNT(*) FROM customers WHERE is_internal = 1")->fetchColumn();

        $spendingRow = $pdo->query("
            SELECT 
                COALESCE(SUM(cost), 0) AS total_spent,
                COALESCE(SUM(CAST(cost AS SIGNED) - CAST(amount_paid AS SIGNED)), 0) AS total_balance
            FROM incubation_jobs
        ")->fetch(PDO::FETCH_ASSOC);

        if ($spendingRow) {
            $stats['total_spent'] = (int) $spendingRow['total_spent'];
            $stats['total_balance'] = max(0, (int) $spendingRow['total_balance']);
        }

        // Search and pagination
        $whereSql = " WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $whereSql .= " AND (c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ? OR c.location LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM customers c {$whereSql}");
        $countStmt->execute($params);
        $totalRows = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($totalRows / $perPage));

        $sql = "
            SELECT 
                c.*,
                COUNT(j.id) AS total_jobs,
                COALESCE(SUM(j.cost), 0) AS total_invoiced,
                COALESCE(SUM(j.amount_paid), 0) AS total_paid,
                COALESCE(SUM(CAST(j.cost AS SIGNED) - CAST(j.amount_paid AS SIGNED)), 0) AS current_balance
            FROM customers c
            LEFT JOIN incubation_jobs j ON j.customer_id = c.id
            {$whereSql}
            GROUP BY c.id
            ORDER BY total_invoiced DESC, c.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return View::render('customers/index', [
            'pageTitle' => 'Customer Directory',
            'activeNav' => 'customers',
            'stats' => $stats,
            'customers' => $customers,
            'search' => $search,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows,
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
        ]);
    }

    public function show(Request $request, int|string $id): Response
    {
        $id = (int) $id;
        Auth::requirePermission('customers', 'view');
        $pdo = DB::pdo();

        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            $_SESSION['flash_error'] = "Customer #{$id} not found.";
            return Response::redirect(url('/customers'));
        }

        // Fetch incubation job history
        $jobStmt = $pdo->prepare("
            SELECT * FROM v_incubation_jobs
            WHERE customer_id = ?
            ORDER BY date_received DESC, id DESC
        ");
        $jobStmt->execute([$id]);
        $jobs = $jobStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch other customers for duplicate merge dropdown
        $otherStmt = $pdo->prepare("
            SELECT id, name, phone, location FROM customers 
            WHERE id != ? 
            ORDER BY name ASC 
            LIMIT 200
        ");
        $otherStmt->execute([$id]);
        $potentialDuplicates = $otherStmt->fetchAll(PDO::FETCH_ASSOC);

        // Customer aggregate stats
        $totalCost = array_sum(array_column($jobs, 'cost'));
        $totalPaid = array_sum(array_column($jobs, 'total_paid'));
        $balance = $totalCost - $totalPaid;
        $eggsSet = array_sum(array_column($jobs, 'eggs_incubated'));
        $chicks = array_sum(array_column($jobs, 'hatched'));
        $avgHatch = $eggsSet > 0 ? round(($chicks / $eggsSet) * 100, 1) : 0;

        // Fetch commercial sales history for this customer
        $salesStmt = $pdo->prepare("
            SELECT s.*, 
                (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) AS items_count
            FROM sales s
            WHERE s.customer_id = ?
            ORDER BY s.sale_date DESC, s.id DESC
            LIMIT 50
        ");
        $salesStmt->execute([$id]);
        $sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalSales = array_sum(array_column($sales, 'total'));
        $totalSalesPaid = array_sum(array_column($sales, 'paid'));
        $salesBalance = max(0, $totalSales - $totalSalesPaid);

        $legacyNames = [];
        if (!empty($customer['legacy_names'])) {
            $decoded = json_decode((string)$customer['legacy_names'], true);
            if (is_array($decoded)) {
                $legacyNames = $decoded;
            }
        }

        // Pagination for incubation jobs
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $totalJobs = count($jobs);
        $totalPages = max(1, (int) ceil($totalJobs / $perPage));
        $paginatedJobs = array_slice($jobs, ($page - 1) * $perPage, $perPage);

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return View::render('customers/show', [
            'pageTitle' => "Customer Profile: {$customer['name']}",
            'activeNav' => 'customers',
            'customer' => $customer,
            'jobs' => $paginatedJobs,
            'totalJobs' => $totalJobs,
            'sales' => $sales,
            'totalSales' => $totalSales,
            'salesBalance' => $salesBalance,
            'potentialDuplicates' => $potentialDuplicates,
            'totalCost' => $totalCost,
            'totalPaid' => $totalPaid,
            'balance' => $balance,
            'eggsSet' => $eggsSet,
            'chicks' => $chicks,
            'avgHatch' => $avgHatch,
            'legacyNames' => $legacyNames,
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
            'page' => $page,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
        ]);
    }

    public function create(Request $request): Response
    {
        Auth::requirePermission('customers', 'create');

        return View::render('customers/create', [
            'pageTitle' => 'Register New Customer',
            'activeNav' => 'customers',
        ]);
    }

    public function store(Request $request): Response
    {
        Auth::requirePermission('customers', 'create');
        $pdo = DB::pdo();

        $name = trim((string) $request->input('name', ''));
        $location = trim((string) $request->input('location', ''));
        $phoneRaw = trim((string) $request->input('phone', ''));
        $email = trim((string) $request->input('email', ''));
        $isInternal = (int) $request->input('is_internal', 0);
        $smsOptIn = (int) $request->input('sms_opt_in', 1);
        $emailOptIn = (int) $request->input('email_opt_in', 1);
        $notes = trim((string) $request->input('notes', ''));

        if ($name === '') {
            $_SESSION['flash_error'] = "Customer name is required.";
            return Response::redirect(url('/customers/create'));
        }

        $phone = $phoneRaw !== '' ? Phone::formatE164($phoneRaw) : null;

        $stmt = $pdo->prepare("
            INSERT INTO customers (name, location, phone, email, is_internal, sms_opt_in, email_opt_in, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$name, $location ?: null, $phone, $email ?: null, $isInternal, $smsOptIn, $emailOptIn, $notes ?: null]);
        $newId = (int) $pdo->lastInsertId();

        Audit::log('create', 'customers', $newId, null, [
            'name' => $name,
            'location' => $location,
            'phone' => $phone,
            'email' => $email,
            'is_internal' => $isInternal,
        ]);

        $_SESSION['flash_success'] = "Customer '{$name}' registered successfully.";
        return Response::redirect(url("/customers/{$newId}"));
    }

    public function edit(Request $request, int|string $id): Response
    {
        $id = (int) $id;
        Auth::requirePermission('customers', 'edit');
        $pdo = DB::pdo();

        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            $_SESSION['flash_error'] = "Customer not found.";
            return Response::redirect(url('/customers'));
        }

        return View::render('customers/edit', [
            'pageTitle' => "Edit Customer: {$customer['name']}",
            'activeNav' => 'customers',
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, int|string $id): Response
    {
        $id = (int) $id;
        Auth::requirePermission('customers', 'edit');
        $pdo = DB::pdo();

        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $old = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$old) {
            $_SESSION['flash_error'] = "Customer not found.";
            return Response::redirect(url('/customers'));
        }

        $name = trim((string) $request->input('name', ''));
        $location = trim((string) $request->input('location', ''));
        $phoneRaw = trim((string) $request->input('phone', ''));
        $email = trim((string) $request->input('email', ''));
        $isInternal = (int) $request->input('is_internal', 0);
        $smsOptIn = (int) $request->input('sms_opt_in', 1);
        $emailOptIn = (int) $request->input('email_opt_in', 1);
        $notes = trim((string) $request->input('notes', ''));

        if ($name === '') {
            $_SESSION['flash_error'] = "Customer name is required.";
            return Response::redirect(url("/customers/{$id}/edit"));
        }

        $phone = $phoneRaw !== '' ? Phone::formatE164($phoneRaw) : null;

        $update = $pdo->prepare("
            UPDATE customers
            SET name = ?, location = ?, phone = ?, email = ?, is_internal = ?, sms_opt_in = ?, email_opt_in = ?, notes = ?
            WHERE id = ?
        ");
        $update->execute([$name, $location ?: null, $phone, $email ?: null, $isInternal, $smsOptIn, $emailOptIn, $notes ?: null, $id]);

        Audit::log('update', 'customers', $id, $old, [
            'name' => $name,
            'location' => $location,
            'phone' => $phone,
            'email' => $email,
            'is_internal' => $isInternal,
            'sms_opt_in' => $smsOptIn,
            'email_opt_in' => $emailOptIn,
            'notes' => $notes,
        ]);

        $_SESSION['flash_success'] = "Customer details updated successfully.";
        return Response::redirect(url("/customers/{$id}"));
    }

    /**
     * Merge duplicate customer into kept customer (Prompt 5 requirement)
     */
    public function merge(Request $request): Response
    {
        Auth::requirePermission('customers', 'edit');
        $pdo = DB::pdo();

        $targetId = (int) $request->input('target_id');
        $sourceId = (int) $request->input('source_id');

        if ($targetId <= 0 || $sourceId <= 0 || $targetId === $sourceId) {
            $_SESSION['flash_error'] = "Invalid customer selection for merge.";
            return Response::redirect(url('/customers'));
        }

        $pdo->beginTransaction();
        try {
            // Check both customers exist
            $targetStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? FOR UPDATE");
            $targetStmt->execute([$targetId]);
            $target = $targetStmt->fetch(PDO::FETCH_ASSOC);

            $sourceStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? FOR UPDATE");
            $sourceStmt->execute([$sourceId]);
            $source = $sourceStmt->fetch(PDO::FETCH_ASSOC);

            if (!$target || !$source) {
                throw new RuntimeException("One or both customers could not be found.");
            }

            // 1. Move incubation jobs
            $moveJobs = $pdo->prepare("UPDATE incubation_jobs SET customer_id = ? WHERE customer_id = ?");
            $moveJobs->execute([$targetId, $sourceId]);
            $jobsMoved = $moveJobs->rowCount();

            // 2. Move sales
            $moveSales = $pdo->prepare("UPDATE sales SET customer_id = ? WHERE customer_id = ?");
            $moveSales->execute([$targetId, $sourceId]);

            // 3. Move flock_events
            $moveEvents = $pdo->prepare("UPDATE flock_events SET customer_id = ? WHERE customer_id = ?");
            $moveEvents->execute([$targetId, $sourceId]);

            // 4. Update legacy_names of the kept target
            $targetLegacy = [];
            if (!empty($target['legacy_names'])) {
                $decoded = json_decode((string)$target['legacy_names'], true);
                if (is_array($decoded)) {
                    $targetLegacy = $decoded;
                }
            }

            // Add source name and phone as historical aliases
            if (!in_array($source['name'], $targetLegacy, true)) {
                $targetLegacy[] = $source['name'];
            }
            if (!empty($source['phone']) && !in_array($source['phone'], $targetLegacy, true)) {
                $targetLegacy[] = $source['phone'];
            }
            if (!empty($source['legacy_names'])) {
                $sDecoded = json_decode((string)$source['legacy_names'], true);
                if (is_array($sDecoded)) {
                    foreach ($sDecoded as $alias) {
                        if (!in_array($alias, $targetLegacy, true)) {
                            $targetLegacy[] = $alias;
                        }
                    }
                }
            }

            $updateLegacy = $pdo->prepare("UPDATE customers SET legacy_names = ? WHERE id = ?");
            $updateLegacy->execute([json_encode($targetLegacy, JSON_UNESCAPED_UNICODE), $targetId]);

            // 5. Delete source customer
            $delStmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
            $delStmt->execute([$sourceId]);

            $pdo->commit();

            Audit::log('merge', 'customers', $targetId, $source, [
                'target_customer_id' => $targetId,
                'merged_source_id' => $sourceId,
                'source_name' => $source['name'],
                'jobs_moved' => $jobsMoved,
                'updated_legacy_names' => $targetLegacy,
            ]);

            $_SESSION['flash_success'] = "Successfully merged customer '{$source['name']}' into '{$target['name']}' ({$jobsMoved} jobs transferred).";
            return Response::redirect(url("/customers/{$targetId}"));
        } catch (Throwable $e) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = "Merge failed: " . $e->getMessage();
            return Response::redirect(url("/customers/{$targetId}"));
        }
    }
}
