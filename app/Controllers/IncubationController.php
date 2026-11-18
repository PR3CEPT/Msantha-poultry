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
use App\Services\IncubationService;
use PDO;
use Throwable;

class IncubationController
{
    public function index(Request $request): Response
    {
        Auth::requirePermission('incubation', 'view');
        $pdo = DB::pdo();

        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        // Overall stats
        $stats = [
            'total_batches' => 0,
            'active_batches' => 0,
            'total_eggs' => 0,
            'total_hatched' => 0,
            'avg_hatch_rate' => 0.0,
            'total_cost' => 0,
            'total_paid' => 0,
            'total_balance' => 0,
        ];

        $summaryRow = $pdo->query("
            SELECT 
                COUNT(*) AS total_batches,
                COALESCE(SUM(CASE WHEN status IN ('received', 'incubating', 'hatching') THEN 1 ELSE 0 END), 0) AS active_batches,
                COALESCE(SUM(eggs_incubated), 0) AS total_eggs,
                COALESCE(SUM(hatched), 0) AS total_hatched,
                COALESCE(SUM(cost), 0) AS total_cost,
                COALESCE(SUM(total_paid), 0) AS total_paid,
                COALESCE(SUM(balance), 0) AS total_balance
            FROM v_incubation_jobs
        ")->fetch(PDO::FETCH_ASSOC);

        if ($summaryRow) {
            $stats['total_batches'] = (int) $summaryRow['total_batches'];
            $stats['active_batches'] = (int) $summaryRow['active_batches'];
            $stats['total_eggs'] = (int) $summaryRow['total_eggs'];
            $stats['total_hatched'] = (int) $summaryRow['total_hatched'];
            $stats['total_cost'] = (int) $summaryRow['total_cost'];
            $stats['total_paid'] = (int) $summaryRow['total_paid'];
            $stats['total_balance'] = (int) $summaryRow['total_balance'];
            if ($stats['total_eggs'] > 0) {
                $stats['avg_hatch_rate'] = round(($stats['total_hatched'] / $stats['total_eggs']) * 100, 1);
            }
        }

        // Dashboard Widget 1: Transfer today (status = incubating AND date_transfer = CURDATE())
        $transferToday = $pdo->query("
            SELECT * FROM v_incubation_jobs
            WHERE date_transfer = CURDATE() AND status IN ('incubating', 'received')
            ORDER BY id DESC LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Dashboard Widget 2: Hatching soon (within next 3 days)
        $hatchingSoon = $pdo->query("
            SELECT * FROM v_incubation_jobs
            WHERE expected_hatch_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
            AND status IN ('incubating', 'hatching')
            ORDER BY expected_hatch_date ASC LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Dashboard Widget 3: Unpaid balances
        $unpaidBalances = $pdo->query("
            SELECT * FROM v_incubation_jobs
            WHERE balance > 0
            ORDER BY balance DESC LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Build list query
        $whereSql = " WHERE 1=1";
        $params = [];

        if ($status !== 'all' && $status !== '') {
            $whereSql .= " AND status = ?";
            $params[] = $status;
        }

        if ($dateFrom !== '') {
            $whereSql .= " AND date_received >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $whereSql .= " AND date_received <= ?";
            $params[] = $dateTo;
        }

        if ($search !== '') {
            $whereSql .= " AND (customer_name LIKE ? OR customer_phone LIKE ? OR legacy_ref LIKE ? OR remarks LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        // Count total
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM v_incubation_jobs {$whereSql}");
        $countStmt->execute($params);
        $totalRows = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($totalRows / $perPage));

        // Get items
        $dataStmt = $pdo->prepare("
            SELECT * FROM v_incubation_jobs 
            {$whereSql} 
            ORDER BY date_received DESC, id DESC 
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $dataStmt->execute($params);
        $jobs = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return View::render('incubation/index', [
            'pageTitle' => 'Incubation Services',
            'activeNav' => 'incubation',
            'stats' => $stats,
            'jobs' => $jobs,
            'search' => $search,
            'status' => $status,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows,
            'transferToday' => $transferToday,
            'hatchingSoon' => $hatchingSoon,
            'unpaidBalances' => $unpaidBalances,
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
        ]);
    }

    public function show(Request $request, int|string $id): Response
    {
        $id = (int) $id;
        Auth::requirePermission('incubation', 'view');
        $pdo = DB::pdo();

        $stmt = $pdo->prepare("SELECT * FROM v_incubation_jobs WHERE id = ?");
        $stmt->execute([$id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            $_SESSION['flash_error'] = "Incubation job #{$id} not found.";
            return Response::redirect(url('/incubation-jobs'));
        }

        // Owner/Msantha internal jobs do not require payment
        $isInternal = (int)($job['customer_is_internal'] ?? 0) === 1 || 
                      str_contains(strtolower((string)($job['customer_name'] ?? '')), 'msantha');
        if ($isInternal) {
            $job['balance'] = 0;
            $job['is_internal_owner'] = true;
        }

        // Payments and allocations
        $payStmt = $pdo->prepare("
            SELECT p.*, u.full_name AS received_by_name
            FROM incubation_payments p
            LEFT JOIN users u ON u.id = p.received_by
            WHERE p.job_id = ?
            ORDER BY p.paid_on DESC, p.id DESC
        ");
        $payStmt->execute([$id]);
        $payments = $payStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch allocations grouped by payment
        $allocStmt = $pdo->prepare("
            SELECT a.*, p.job_id
            FROM incubation_allocations a
            JOIN incubation_payments p ON p.id = a.payment_id
            WHERE p.job_id = ?
            ORDER BY a.payment_id DESC, a.id ASC
        ");
        $allocStmt->execute([$id]);
        $rawAllocs = $allocStmt->fetchAll(PDO::FETCH_ASSOC);

        $allocationsByPayment = [];
        foreach ($rawAllocs as $ra) {
            $allocationsByPayment[(int)$ra['payment_id']][] = $ra;
        }

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return View::render('incubation/show', [
            'pageTitle' => "Incubation Batch #{$job['id']} — {$job['customer_name']}",
            'activeNav' => 'incubation',
            'job' => $job,
            'payments' => $payments,
            'allocationsByPayment' => $allocationsByPayment,
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
        ]);
    }

    public function receive(Request $request): Response
    {
        Auth::requirePermission('incubation', 'create');
        $pdo = DB::pdo();

        $preselectedCustomerId = (int) $request->query('customer_id', 0);

        $customers = $pdo->query("SELECT id, name, phone, location FROM customers ORDER BY name ASC LIMIT 300")->fetchAll(PDO::FETCH_ASSOC);
        $breeds = $pdo->query("
            SELECT b.*, s.name AS species_name, s.incubation_days 
            FROM breeds b 
            JOIN species s ON s.id = b.species_id 
            ORDER BY s.name ASC, b.name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        $species = $pdo->query("SELECT * FROM species ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        return View::render('incubation/receive', [
            'pageTitle' => 'Receive Eggs for Incubation',
            'activeNav' => 'incubation',
            'customers' => $customers,
            'breeds' => $breeds,
            'species' => $species,
            'preselectedCustomerId' => $preselectedCustomerId,
            'flashError' => $flashError,
        ]);
    }

    public function store(Request $request): Response
    {
        Auth::requirePermission('incubation', 'create');
        $pdo = DB::pdo();
        $incService = new IncubationService($pdo);

        $customerId = (int) $request->input('customer_id', 0);
        $newCustomerName = trim((string) $request->input('new_customer_name', ''));
        $newCustomerPhone = trim((string) $request->input('new_customer_phone', ''));

        if ($customerId <= 0 && $newCustomerName !== '') {
            $formattedPhone = $newCustomerPhone !== '' ? Phone::normalize($newCustomerPhone) : null;
            $cInsert = $pdo->prepare("INSERT INTO customers (name, phone, created_at) VALUES (?, ?, NOW())");
            $cInsert->execute([$newCustomerName, $formattedPhone]);
            $customerId = (int) $pdo->lastInsertId();
        }

        if ($customerId <= 0) {
            $_SESSION['flash_error'] = "Please select or enter a valid customer.";
            return Response::redirect(url('/incubation/receive'));
        }

        $speciesId = (int) $request->input('species_id', 1);
        $breedId = (int) $request->input('breed_id', 0) ?: null;
        $dateReceived = (string) $request->input('date_received', date('Y-m-d'));
        $dateIncubated = (string) $request->input('date_incubated', $dateReceived);
        $eggsReceived = max(0, (int) $request->input('eggs_received', 0));
        $eggsIncubated = max(0, (int) $request->input('eggs_incubated', $eggsReceived));
        $ratePerEgg = max(0, (int) $request->input('rate_per_egg', 120));
        $remarks = trim((string) $request->input('remarks', ''));

        // Auto date calculations based on species
        $dates = $incService->calculateDates($speciesId, $dateIncubated);
        $cost = $incService->calculateCost($eggsIncubated, $ratePerEgg);
        $batchCode = IncubationService::generateBatchCode($customerId, $pdo);

        $stmt = $pdo->prepare("
            INSERT INTO incubation_jobs (
                customer_id, batch_code, breed_id, date_received, eggs_received, eggs_incubated,
                date_incubated, date_transfer, expected_hatch_date, rate_per_egg, cost,
                amount_paid, status, remarks, created_by, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                0, 'incubating', ?, ?, NOW()
            )
        ");
        $stmt->execute([
            $customerId,
            $batchCode,
            $breedId,
            $dateReceived,
            $eggsReceived,
            $eggsIncubated,
            $dateIncubated,
            $dates['date_transfer'],
            $dates['expected_hatch_date'],
            $ratePerEgg,
            $cost,
            $remarks ?: null,
            Auth::id(),
        ]);
        $newJobId = (int) $pdo->lastInsertId();

        Audit::log('create', 'incubation_jobs', $newJobId, null, [
            'customer_id' => $customerId,
            'batch_code' => $batchCode,
            'eggs_incubated' => $eggsIncubated,
            'cost' => $cost,
            'expected_hatch_date' => $dates['expected_hatch_date'],
        ]);

        $_SESSION['flash_success'] = "Incubation batch {$batchCode} (#{$newJobId}) created. Expected transfer: {$dates['date_transfer']}, Hatch: {$dates['expected_hatch_date']}.";
        return Response::redirect(url("/incubation-jobs/{$newJobId}"));
    }

    public function recordHatch(Request $request, int $id): Response
    {
        Auth::requirePermission('incubation', 'edit');
        $incService = new IncubationService();

        $infertile = $request->input('infertile') !== '' ? (int) $request->input('infertile') : null;
        $fertile = $request->input('fertile') !== '' ? (int) $request->input('fertile') : null;
        $hatched = $request->input('hatched') !== '' ? (int) $request->input('hatched') : null;
        $dateHatched = (string) $request->input('date_hatched', date('Y-m-d'));

        try {
            $incService->recordCandlingAndHatch($id, $infertile, $fertile, $hatched, $dateHatched, Auth::id());
            $_SESSION['flash_success'] = "Hatch results recorded successfully for Batch #{$id}.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Error recording hatch: " . $e->getMessage();
        }

        return Response::redirect(url("/incubation-jobs/{$id}"));
    }

    public function addPayment(Request $request, int $id): Response
    {
        Auth::requirePermission('incubation', 'edit');
        $incService = new IncubationService();

        $amount = (int) $request->input('amount', 0);
        $method = (string) $request->input('method', 'cash');
        $paidOn = (string) $request->input('paid_on', date('Y-m-d'));

        try {
            $paymentId = $incService->recordPayment($id, $amount, $method, $paidOn, Auth::id());
            $_SESSION['flash_success'] = "Payment of " . number_format($amount) . " MWK recorded with automated 25/15/30/20/10 allocations.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Payment failed: " . $e->getMessage();
        }

        return Response::redirect(url("/incubation-jobs/{$id}"));
    }

    public function receipt(Request $request, int|string $id): Response
    {
        $id = (int) $id;
        Auth::requirePermission('incubation', 'view');
        $pdo = DB::pdo();

        $jobStmt = $pdo->prepare("SELECT * FROM v_incubation_jobs WHERE id = ?");
        $jobStmt->execute([$id]);
        $job = $jobStmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            return Response::html("<h1>Job not found</h1>", 404);
        }

        // Owner internal jobs do not require payment
        if ((int)($job['customer_is_internal'] ?? 0) === 1 || str_contains(strtolower((string)($job['customer_name'] ?? '')), 'msantha')) {
            $job['balance'] = 0;
            $job['is_internal_owner'] = true;
        }

        $payments = $pdo->prepare("SELECT * FROM incubation_payments WHERE job_id = ? ORDER BY paid_on ASC, id ASC");
        $payments->execute([$id]);
        $payRows = $payments->fetchAll(PDO::FETCH_ASSOC);

        return View::render('incubation/receipt', [
            'pageTitle' => "Receipt — Batch #{$job['id']}",
            'job' => $job,
            'payments' => $payRows,
        ]);
    }

    public function certificate(Request $request, int|string $id): Response
    {
        $id = (int) $id;
        Auth::requirePermission('incubation', 'view');
        $pdo = DB::pdo();

        $jobStmt = $pdo->prepare("SELECT * FROM v_incubation_jobs WHERE id = ?");
        $jobStmt->execute([$id]);
        $job = $jobStmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            return Response::html("<h1>Job not found</h1>", 404);
        }

        return View::render('incubation/certificate', [
            'pageTitle' => "Hatch Certificate — Batch #{$job['id']}",
            'job' => $job,
        ]);
    }

    public function recordCollection(Request $request, int|string $id): Response
    {
        $id = (int) $id;
        Auth::requirePermission('incubation', 'edit');
        $pdo = DB::pdo();

        $stmt = $pdo->prepare("SELECT * FROM incubation_jobs WHERE id = ?");
        $stmt->execute([$id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            $_SESSION['flash_error'] = "Incubation batch #{$id} not found.";
            return Response::redirect(url('/incubation-jobs'));
        }

        $dateCollected = trim((string) $request->input('date_collected', date('Y-m-d')));
        $chicksCollected = max(0, (int) $request->input('chicks_collected', $job['hatched'] ?? 0));
        $collectorNotes = trim((string) $request->input('collector_notes', ''));

        // Format remarks to retain previous remarks and append collection details
        $prevRemarks = trim((string)($job['remarks'] ?? ''));
        $collectionStamp = "[Collected: " . date('d-M-Y', strtotime($dateCollected)) . " | {$chicksCollected} chicks" . ($collectorNotes !== '' ? " | Notes: {$collectorNotes}" : "") . "]";
        $newRemarks = $prevRemarks !== '' ? "{$prevRemarks} | {$collectionStamp}" : $collectionStamp;

        $up = $pdo->prepare("
            UPDATE incubation_jobs
            SET status = 'collected',
                remarks = ?
            WHERE id = ?
        ");
        $up->execute([$newRemarks, $id]);

        Audit::log('update', 'incubation_jobs', $id, $job, [
            'status' => 'collected',
            'date_collected' => $dateCollected,
            'chicks_collected' => $chicksCollected,
            'collector_notes' => $collectorNotes,
        ]);

        $_SESSION['flash_success'] = "Batch " . ($job['batch_code'] ? "({$job['batch_code']})" : "#{$id}") . " successfully marked as Collected ({$chicksCollected} chicks handed over).";
        return Response::redirect(url("/incubation-jobs/{$id}"));
    }

    public function edit(Request $request, int|string $id): Response
    {
        $id = (int) $id;
        Auth::requirePermission('incubation', 'edit');
        $pdo = DB::pdo();

        $jobStmt = $pdo->prepare("SELECT * FROM v_incubation_jobs WHERE id = ?");
        $jobStmt->execute([$id]);
        $job = $jobStmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            $_SESSION['flash_error'] = "Incubation batch #{$id} not found.";
            return Response::redirect(url('/incubation-jobs'));
        }

        // Fetch breed details to know species_id
        $speciesId = 1;
        if (!empty($job['breed_id'])) {
            $bStmt = $pdo->prepare("SELECT species_id FROM breeds WHERE id = ?");
            $bStmt->execute([$job['breed_id']]);
            $speciesId = (int) ($bStmt->fetchColumn() ?: 1);
        }
        $job['species_id'] = $speciesId;

        $customers = $pdo->query("SELECT id, name, phone, location FROM customers ORDER BY name ASC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
        $breeds = $pdo->query("
            SELECT b.*, s.name AS species_name, s.incubation_days 
            FROM breeds b 
            JOIN species s ON s.id = b.species_id 
            ORDER BY s.name ASC, b.name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        $species = $pdo->query("SELECT * FROM species ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $incubators = $pdo->query("SELECT id, name, capacity, is_active FROM incubators WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        return View::render('incubation/edit', [
            'pageTitle' => "Edit Incubation Batch " . ($job['batch_code'] ? "({$job['batch_code']})" : "#{$job['id']}"),
            'activeNav' => 'incubation',
            'job' => $job,
            'customers' => $customers,
            'breeds' => $breeds,
            'species' => $species,
            'incubators' => $incubators,
            'flashError' => $flashError,
        ]);
    }

    public function update(Request $request, int|string $id): Response
    {
        $id = (int) $id;
        Auth::requirePermission('incubation', 'edit');
        $pdo = DB::pdo();
        $incService = new IncubationService($pdo);

        $stmt = $pdo->prepare("SELECT * FROM incubation_jobs WHERE id = ?");
        $stmt->execute([$id]);
        $oldJob = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$oldJob) {
            $_SESSION['flash_error'] = "Incubation batch #{$id} not found.";
            return Response::redirect(url('/incubation-jobs'));
        }

        $customerId = (int) $request->input('customer_id', $oldJob['customer_id']);
        if ($customerId <= 0) {
            $_SESSION['flash_error'] = "A valid customer is required.";
            return Response::redirect(url("/incubation-jobs/{$id}/edit"));
        }

        $speciesId = (int) $request->input('species_id', 1);
        $breedId = (int) $request->input('breed_id', 0) ?: null;
        $incubatorId = (int) $request->input('incubator_id', 0) ?: null;

        $dateReceived = (string) $request->input('date_received', $oldJob['date_received']);
        $dateIncubated = (string) $request->input('date_incubated', $oldJob['date_incubated'] ?? $dateReceived);

        $eggsReceived = max(0, (int) $request->input('eggs_received', $oldJob['eggs_received']));
        $eggsIncubated = max(0, (int) $request->input('eggs_incubated', $oldJob['eggs_incubated']));
        $ratePerEgg = max(0, (int) $request->input('rate_per_egg', $oldJob['rate_per_egg']));
        $status = trim((string) $request->input('status', $oldJob['status']));
        $remarks = trim((string) $request->input('remarks', $oldJob['remarks'] ?? ''));

        // Candling / hatch results if completed or updated
        $infertile = $request->input('infertile') !== '' && $request->input('infertile') !== null ? (int) $request->input('infertile') : ($oldJob['infertile'] !== null ? (int)$oldJob['infertile'] : null);
        $fertile = $request->input('fertile') !== '' && $request->input('fertile') !== null ? (int) $request->input('fertile') : ($oldJob['fertile'] !== null ? (int)$oldJob['fertile'] : null);
        $hatched = $request->input('hatched') !== '' && $request->input('hatched') !== null ? (int) $request->input('hatched') : ($oldJob['hatched'] !== null ? (int)$oldJob['hatched'] : null);
        $dateHatched = trim((string) $request->input('date_hatched', '')) ?: ($oldJob['date_hatched'] ?? null);

        // Date calculations or manual overrides
        $customTransfer = trim((string) $request->input('date_transfer', ''));
        $customHatch = trim((string) $request->input('expected_hatch_date', ''));

        if ($customTransfer !== '' && $customHatch !== '') {
            $dateTransfer = $customTransfer;
            $expectedHatchDate = $customHatch;
        } else {
            $dates = $incService->calculateDates($speciesId, $dateIncubated);
            $dateTransfer = $customTransfer ?: $dates['date_transfer'];
            $expectedHatchDate = $customHatch ?: $dates['expected_hatch_date'];
        }

        // Cost calculation
        $cost = $incService->calculateCost($eggsIncubated, $ratePerEgg);

        // Batch code: if customer changed or batch_code is empty, generate new sequence
        $batchCode = $oldJob['batch_code'];
        if (empty($batchCode) || (int)$oldJob['customer_id'] !== $customerId) {
            $batchCode = IncubationService::generateBatchCode($customerId, $pdo);
        }

        $upStmt = $pdo->prepare("
            UPDATE incubation_jobs
            SET customer_id = ?,
                batch_code = ?,
                breed_id = ?,
                incubator_id = ?,
                date_received = ?,
                eggs_received = ?,
                eggs_incubated = ?,
                date_incubated = ?,
                date_transfer = ?,
                expected_hatch_date = ?,
                date_hatched = ?,
                infertile = ?,
                fertile = ?,
                hatched = ?,
                rate_per_egg = ?,
                cost = ?,
                status = ?,
                remarks = ?
            WHERE id = ?
        ");
        $upStmt->execute([
            $customerId,
            $batchCode,
            $breedId,
            $incubatorId,
            $dateReceived,
            $eggsReceived,
            $eggsIncubated,
            $dateIncubated,
            $dateTransfer,
            $expectedHatchDate,
            $dateHatched,
            $infertile,
            $fertile,
            $hatched,
            $ratePerEgg,
            $cost,
            $status,
            $remarks ?: null,
            $id,
        ]);

        Audit::log('update', 'incubation_jobs', $id, $oldJob, [
            'customer_id' => $customerId,
            'batch_code' => $batchCode,
            'breed_id' => $breedId,
            'eggs_incubated' => $eggsIncubated,
            'cost' => $cost,
            'date_transfer' => $dateTransfer,
            'expected_hatch_date' => $expectedHatchDate,
            'status' => $status,
        ]);

        $_SESSION['flash_success'] = "Incubation batch " . ($batchCode ? "({$batchCode})" : "#{$id}") . " updated successfully.";
        return Response::redirect(url("/incubation-jobs/{$id}"));
    }

    public function destroy(Request $request, int|string $id): Response
    {
        $id = (int) $id;
        Auth::requireRole(['owner', 'manager']);
        $pdo = DB::pdo();

        $stmt = $pdo->prepare("SELECT * FROM incubation_jobs WHERE id = ?");
        $stmt->execute([$id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            $_SESSION['flash_error'] = "Incubation batch #{$id} not found.";
            return Response::redirect(url('/incubation-jobs'));
        }

        // Safety check: Are there recorded payments?
        $pStmt = $pdo->prepare("SELECT COUNT(*) FROM incubation_payments WHERE job_id = ?");
        $pStmt->execute([$id]);
        $payCount = (int) $pStmt->fetchColumn();
        $totalPaid = (int) ($job['amount_paid'] ?? 0);

        if ($payCount > 0 || $totalPaid > 0) {
            $_SESSION['flash_error'] = "Cannot delete batch #{$id} because payments have already been recorded. Deleting paid transactions would compromise accounting and cash book integrity. Please void or refund payments before deleting.";
            return Response::redirect(url("/incubation-jobs/{$id}"));
        }

        // Hard delete allocations and the job
        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE a FROM incubation_allocations a JOIN incubation_payments p ON p.id = a.payment_id WHERE p.job_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM incubation_payments WHERE job_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM incubation_jobs WHERE id = ?")->execute([$id]);

            Audit::log('delete', 'incubation_jobs', $id, $job, null);
            $pdo->commit();

            $_SESSION['flash_success'] = "Batch " . ($job['batch_code'] ? "({$job['batch_code']})" : "#{$id}") . " has been permanently deleted.";
            return Response::redirect(url('/incubation-jobs'));
        } catch (Throwable $e) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = "Failed to delete batch: " . $e->getMessage();
            return Response::redirect(url("/incubation-jobs/{$id}"));
        }
    }
}
