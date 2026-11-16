<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\FlockService;
use App\Services\PricingService;
use App\Services\VaccinationService;
use PDO;
use Throwable;

class FlockController
{
    public function index(Request $request): Response
    {
        Auth::requirePermission('flocks', 'view');
        $pdo = DB::pdo();

        $tab = $request->query('tab', 'batches');
        $search = trim((string) $request->query('q', ''));
        $statusFilter = trim((string) $request->query('status', 'all'));

        // Overall stats
        $stats = [
            'total_flocks' => 0,
            'active_flocks' => 0,
            'current_stock' => 0,
            'total_initial' => 0,
            'total_deaths' => 0,
            'total_sold' => 0,
        ];

        $stockRow = $pdo->query("
            SELECT 
                COUNT(*) AS total_flocks,
                COALESCE(SUM(CASE WHEN current_stock > 0 THEN 1 ELSE 0 END), 0) AS active_flocks,
                COALESCE(SUM(current_stock), 0) AS total_current,
                COALESCE(SUM(start_count), 0) AS total_initial,
                COALESCE(SUM(deaths_todate), 0) AS total_deaths,
                COALESCE(SUM(sold_count), 0) AS total_sold
            FROM v_flock_status
        ")->fetch(PDO::FETCH_ASSOC);

        if ($stockRow) {
            $stats['total_flocks'] = (int) $stockRow['total_flocks'];
            $stats['active_flocks'] = (int) $stockRow['active_flocks'];
            $stats['current_stock'] = (int) $stockRow['total_current'];
            $stats['total_initial'] = (int) $stockRow['total_initial'];
            $stats['total_deaths'] = (int) $stockRow['total_deaths'];
            $stats['total_sold'] = (int) $stockRow['total_sold'];
        }

        // Aggregate population across batches by breed
        $breedStats = $pdo->query("
            SELECT 
                b.id AS breed_id,
                b.name AS breed_name,
                s.name AS species_name,
                COUNT(v.flock_id) AS total_batches,
                COALESCE(SUM(CASE WHEN v.current_stock > 0 THEN 1 ELSE 0 END), 0) AS active_batches,
                COALESCE(SUM(v.start_count), 0) AS total_initial,
                COALESCE(SUM(v.deaths_todate), 0) AS total_deaths,
                COALESCE(SUM(v.sold_count), 0) AS total_sold,
                COALESCE(SUM(v.current_stock), 0) AS total_current_birds
            FROM breeds b
            JOIN species s ON s.id = b.species_id
            LEFT JOIN v_flock_status v ON v.breed_id = b.id
            GROUP BY b.id, b.name, s.name
            HAVING total_batches > 0 OR total_current_birds > 0
            ORDER BY total_current_birds DESC, b.name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        if ($tab === 'events') {
            $page = max(1, (int) $request->query('page', 1));
            $perPage = 25;
            $offset = ($page - 1) * $perPage;

            // Authoritative events list
            $sql = "
                SELECT e.*, f.batch_code, f.breed_name
                FROM flock_events e
                JOIN flocks f ON f.id = e.flock_id
                WHERE 1=1
            ";
            $params = [];

            if ($search !== '') {
                $sql .= " AND (f.batch_code LIKE ? OR f.breed_name LIKE ? OR e.remarks LIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            $countSql = "SELECT COUNT(*) FROM flock_events e JOIN flocks f ON f.id = e.flock_id WHERE 1=1";
            if ($search !== '') $countSql .= " AND (f.batch_code LIKE ? OR f.breed_name LIKE ? OR e.remarks LIKE ?)";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalRows = (int) $countStmt->fetchColumn();
            $totalPages = max(1, (int) ceil($totalRows / $perPage));

            $sql .= " ORDER BY e.event_date DESC, e.id DESC LIMIT {$perPage} OFFSET {$offset}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return View::render('flocks/index', [
                'pageTitle' => 'Flock Events & Mortality',
                'activeNav' => 'flocks',
                'tab' => 'events',
                'stats' => $stats,
                'breedStats' => $breedStats,
                'events' => $events,
                'search' => $search,
                'page' => $page,
                'totalPages' => $totalPages,
                'totalRows' => $totalRows,
                'perPage' => $perPage,
            ]);
        }

        // Default: Flocks list
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT v.*, b.egg_tray_price
            FROM v_flock_status v
            LEFT JOIN breeds b ON b.id = v.breed_id
            WHERE 1=1
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (v.batch_code LIKE ? OR v.breed_name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($statusFilter === 'active') {
            $sql .= " AND v.current_stock > 0";
        } elseif ($statusFilter === 'closed') {
            $sql .= " AND v.current_stock <= 0";
        }

        $countSql = "SELECT COUNT(*) FROM v_flock_status v LEFT JOIN breeds b ON b.id = v.breed_id WHERE 1=1";
        if ($search !== '') $countSql .= " AND (v.batch_code LIKE ? OR v.breed_name LIKE ?)";
        if ($statusFilter === 'active') $countSql .= " AND v.current_stock > 0";
        elseif ($statusFilter === 'closed') $countSql .= " AND v.current_stock <= 0";

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRows = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($totalRows / $perPage));

        $sql .= " ORDER BY v.current_stock DESC, v.flock_id DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $flocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return View::render('flocks/index', [
            'pageTitle' => 'Flock Management',
            'activeNav' => 'flocks',
            'tab' => 'batches',
            'stats' => $stats,
            'breedStats' => $breedStats,
            'flocks' => $flocks,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows,
            'perPage' => $perPage,
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
        ]);
    }

    public function show(Request $request, int|string $id): Response
    {
        $id = (int) $id;
        Auth::requirePermission('flocks', 'view');
        $pdo = DB::pdo();
        $flockService = new FlockService($pdo);
        $pricingService = new PricingService($pdo);
        $vaccService = new VaccinationService($pdo);

        $stmt = $pdo->prepare("SELECT * FROM v_flock_status WHERE flock_id = ?");
        $stmt->execute([$id]);
        $flock = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$flock) {
            $_SESSION['flash_error'] = "Flock batch #{$id} not found.";
            return Response::redirect(url('/flocks'));
        }

        // Age calculation
        $age = $flockService->computeAge((string)$flock['start_date']);

        // Pricing estimate
        $estPrice = $pricingService->getPrice((int)$flock['breed_id'], $age['weeks']);

        // Vaccination schedule
        $vaccinations = $vaccService->getFlockVaccinations($id);

        // Chart trajectory (stock & cumulative deaths)
        $trajectory = $flockService->getFlockTrajectory($id);

        // Events timeline
        $eventsStmt = $pdo->prepare("
            SELECT * FROM flock_events 
            WHERE flock_id = ? 
            ORDER BY event_date DESC, id DESC 
            LIMIT 50
        ");
        $eventsStmt->execute([$id]);
        $events = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return View::render('flocks/show', [
            'pageTitle' => "Batch: {$flock['batch_code']} ({$flock['breed_name']})",
            'activeNav' => 'flocks',
            'flock' => $flock,
            'age' => $age,
            'estPrice' => $estPrice,
            'vaccinations' => $vaccinations,
            'trajectory' => $trajectory,
            'events' => $events,
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
        ]);
    }

    public function create(Request $request): Response
    {
        Auth::requirePermission('flocks', 'create');
        $pdo = DB::pdo();

        $breeds = $pdo->query("SELECT * FROM breeds ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        return View::render('flocks/create', [
            'pageTitle' => 'New Flock Intake Batch',
            'activeNav' => 'flocks',
            'breeds' => $breeds,
        ]);
    }

    public function store(Request $request): Response
    {
        Auth::requirePermission('flocks', 'create');
        $pdo = DB::pdo();

        $batchCode = trim((string) $request->input('batch_code', ''));
        $breedId = (int) $request->input('breed_id', 1);
        $startDate = (string) $request->input('start_date', date('Y-m-d'));
        $startCount = max(1, (int) $request->input('start_count', 0));
        $purpose = (string) $request->input('purpose', 'mixed');
        $source = (string) $request->input('source', 'hatched_own');
        $notes = trim((string) $request->input('notes', ''));

        if ($batchCode === '') {
            $_SESSION['flash_error'] = "Batch code is required.";
            return Response::redirect(url('/flocks/create'));
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO flocks (batch_code, breed_id, start_date, start_count, purpose, source, notes, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([$batchCode, $breedId, $startDate, $startCount, $purpose, $source, $notes ?: null]);
            $newId = (int) $pdo->lastInsertId();

            Audit::log('create', 'flocks', $newId, null, [
                'batch_code' => $batchCode,
                'breed_id' => $breedId,
                'start_count' => $startCount,
                'start_date' => $startDate,
            ]);

            $_SESSION['flash_success'] = "Flock batch '{$batchCode}' registered with {$startCount} birds.";
            return Response::redirect(url("/flocks/{$newId}"));
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Failed to create flock: " . $e->getMessage();
            return Response::redirect(url('/flocks/create'));
        }
    }

    public function quickEntry(Request $request): Response
    {
        Auth::requirePermission('flocks', 'edit');
        $pdo = DB::pdo();

        $selectedFlockId = (int) $request->query('flock_id', 0);

        // Fetch active flocks
        $activeFlocks = $pdo->query("
            SELECT flock_id, batch_code, breed_name, current_stock 
            FROM v_flock_status 
            WHERE current_stock > 0 
            ORDER BY batch_code ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $customers = $pdo->query("SELECT id, name, phone FROM customers ORDER BY name ASC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return View::render('flocks/quick_entry', [
            'pageTitle' => 'Quick Daily Flock Entry',
            'activeNav' => 'flocks',
            'activeFlocks' => $activeFlocks,
            'customers' => $customers,
            'selectedFlockId' => $selectedFlockId,
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
        ]);
    }

    public function saveQuickEntry(Request $request): Response
    {
        Auth::requirePermission('flocks', 'edit');
        $flockService = new FlockService();

        $flockId = (int) $request->input('flock_id', 0);
        $date = (string) $request->input('entry_date', date('Y-m-d'));

        if ($flockId <= 0) {
            $_SESSION['flash_error'] = "Please select a valid flock.";
            return Response::redirect(url('/flocks/quick-entry'));
        }

        $entryData = [
            'deaths' => (int) $request->input('deaths', 0),
            'sold' => (int) $request->input('sold', 0),
            'sold_price' => (int) $request->input('sold_price', 0),
            'customer_id' => (int) $request->input('customer_id', 0),
            'added' => (int) $request->input('added', 0),
            'culled' => (int) $request->input('culled', 0),
            'consumed' => (int) $request->input('consumed', 0),
            'vaccine' => trim((string) $request->input('vaccine', '')),
            'remarks' => trim((string) $request->input('remarks', '')),
        ];

        try {
            $result = $flockService->recordDailyEntry($flockId, $date, $entryData, Auth::id());

            if ($result['mortality_alert']) {
                $_SESSION['flash_error'] = "ALERT: High mortality recorded ({$result['mortality_rate']}%) on {$date}! Exceeds 2.0% daily safety threshold.";
            } else {
                $_SESSION['flash_success'] = "Daily entry recorded for {$date}. Current stock: " . number_format($result['stock_after']) . " birds.";
            }

            return Response::redirect(url("/flocks/{$flockId}"));
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Entry failed: " . $e->getMessage();
            return Response::redirect(url('/flocks/quick-entry?flock_id=' . $flockId));
        }
    }

    public function priceLadder(Request $request): Response
    {
        Auth::requirePermission('flocks', 'view');
        $pdo = DB::pdo();
        $pricingService = new PricingService($pdo);

        $breeds = $pdo->query("SELECT * FROM breeds ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $selectedBreedId = (int) $request->query('breed_id', $breeds[0]['id'] ?? 1);

        $ladder = $pricingService->getLadder($selectedBreedId);

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return View::render('flocks/price_ladder', [
            'pageTitle' => 'Flock Price Ladder (Age-Based Pricing)',
            'activeNav' => 'flocks',
            'breeds' => $breeds,
            'selectedBreedId' => $selectedBreedId,
            'ladder' => $ladder,
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
        ]);
    }

    public function savePriceLadder(Request $request): Response
    {
        Auth::requireRole(['owner', 'manager']);
        $pricingService = new PricingService();

        $breedId = (int) $request->input('breed_id');
        $fromWeek = max(1, (int) $request->input('from_week', 1));
        $price = max(0, (int) $request->input('price', 0));
        $effectiveFrom = (string) $request->input('effective_from', date('Y-m-d'));

        try {
            $pricingService->addTier($breedId, $fromWeek, $price, $effectiveFrom, Auth::id());
            $_SESSION['flash_success'] = "Price ladder tier saved: Week {$fromWeek}+ set to " . number_format($price) . " MWK.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Error saving price ladder: " . $e->getMessage();
        }

        return Response::redirect(url("/flocks/price-ladder?breed_id={$breedId}"));
    }

    public function close(Request $request, int|string $id): Response
    {
        $id = (int) $id;
        Auth::requireRole(['owner', 'manager']);
        $flockService = new FlockService();

        $notes = trim((string) $request->input('notes', ''));

        try {
            $flockService->closeFlock($id, $notes, Auth::id());
            $_SESSION['flash_success'] = "Batch #{$id} has been marked as CLOSED.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Could not close batch: " . $e->getMessage();
        }

        return Response::redirect(url("/flocks/{$id}"));
    }

    public function edit(Request $request, int|string $id): Response
    {
        $id = (int) $id;
        Auth::requirePermission('flocks', 'edit');
        $pdo = DB::pdo();

        $stmt = $pdo->prepare("SELECT * FROM flocks WHERE id = ?");
        $stmt->execute([$id]);
        $flock = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$flock) {
            $_SESSION['flash_error'] = "Flock batch #{$id} not found.";
            return Response::redirect(url('/flocks'));
        }

        $breeds = $pdo->query("SELECT * FROM breeds ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return View::render('flocks/edit', [
            'pageTitle' => "Edit Batch: {$flock['batch_code']}",
            'activeNav' => 'flocks',
            'flock' => $flock,
            'breeds' => $breeds,
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
        ]);
    }

    public function update(Request $request, int|string $id): Response
    {
        $id = (int) $id;
        Auth::requirePermission('flocks', 'edit');
        $pdo = DB::pdo();

        $batchCode = trim((string) $request->input('batch_code', ''));
        $breedId = (int) $request->input('breed_id', 1);
        $startDate = (string) $request->input('start_date', date('Y-m-d'));
        $startCount = max(1, (int) $request->input('start_count', 0));
        $purpose = (string) $request->input('purpose', 'mixed');
        $source = (string) $request->input('source', 'hatched_own');
        $status = (string) $request->input('status', 'active');
        $notes = trim((string) $request->input('notes', ''));

        if ($batchCode === '') {
            $_SESSION['flash_error'] = "Batch code is required.";
            return Response::redirect(url("/flocks/{$id}/edit"));
        }

        try {
            $oldStmt = $pdo->prepare("SELECT * FROM flocks WHERE id = ?");
            $oldStmt->execute([$id]);
            $oldFlock = $oldStmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                UPDATE flocks 
                SET batch_code = ?, breed_id = ?, start_date = ?, start_count = ?, purpose = ?, source = ?, status = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([$batchCode, $breedId, $startDate, $startCount, $purpose, $source, $status, $notes ?: null, $id]);

            Audit::log('update', 'flocks', $id, $oldFlock, [
                'batch_code' => $batchCode,
                'breed_id' => $breedId,
                'start_count' => $startCount,
                'start_date' => $startDate,
                'status' => $status,
            ]);

            $_SESSION['flash_success'] = "Flock batch '{$batchCode}' updated successfully.";
            return Response::redirect(url("/flocks/{$id}"));
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Failed to update flock: " . $e->getMessage();
            return Response::redirect(url("/flocks/{$id}/edit"));
        }
    }
}
