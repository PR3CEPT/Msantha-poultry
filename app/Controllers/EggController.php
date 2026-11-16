<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use PDO;
use Throwable;

class EggController
{
    public function index(Request $request): Response
    {
        Auth::requirePermission('eggs', 'view');
        $pdo = DB::pdo();

        $search = trim((string) $request->query('q', ''));
        $startDate = trim((string) $request->query('start_date', ''));
        $endDate = trim((string) $request->query('end_date', ''));

        // Overall stats
        $stats = [
            'total_collected' => 0,
            'total_trays' => 0,
            'total_broken' => 0,
            'broken_rate' => 0.0,
            'total_consumed' => 0,
            'total_incubated' => 0,
            'total_sold' => 0,
        ];

        $eggRow = $pdo->query("
            SELECT 
                COALESCE(SUM(collected), 0) AS total_collected,
                COALESCE(SUM(broken), 0) AS total_broken,
                COALESCE(SUM(consumed), 0) AS total_consumed,
                COALESCE(SUM(incubated), 0) AS total_incubated,
                COALESCE(SUM(sold), 0) AS total_sold
            FROM egg_production_daily
        ")->fetch(PDO::FETCH_ASSOC);

        if ($eggRow) {
            $stats['total_collected'] = (int) $eggRow['total_collected'];
            $stats['total_trays'] = (int) floor($stats['total_collected'] / 30);
            $stats['total_broken'] = (int) $eggRow['total_broken'];
            $stats['total_consumed'] = (int) $eggRow['total_consumed'];
            $stats['total_incubated'] = (int) $eggRow['total_incubated'];
            $stats['total_sold'] = (int) $eggRow['total_sold'];
            if ($stats['total_collected'] > 0) {
                $stats['broken_rate'] = round(($stats['total_broken'] / $stats['total_collected']) * 100, 2);
            }
        }

        $sql = "
            SELECT e.*, b.name AS breed_name, b.egg_tray_price, f.batch_code
            FROM egg_production_daily e
            LEFT JOIN breeds b ON b.id = e.breed_id
            LEFT JOIN flocks f ON f.id = e.flock_id
            WHERE 1=1
        ";
        $params = [];

        if ($startDate !== '') {
            $sql .= " AND e.prod_date >= ?";
            $params[] = $startDate;
        }

        if ($endDate !== '') {
            $sql .= " AND e.prod_date <= ?";
            $params[] = $endDate;
        }

        if ($search !== '') {
            $sql .= " AND (b.name LIKE ? OR f.batch_code LIKE ? OR e.remarks LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $countSql = "
            SELECT COUNT(*)
            FROM egg_production_daily e
            LEFT JOIN breeds b ON b.id = e.breed_id
            LEFT JOIN flocks f ON f.id = e.flock_id
            WHERE 1=1
        ";
        if ($startDate !== '') $countSql .= " AND e.prod_date >= ?";
        if ($endDate !== '') $countSql .= " AND e.prod_date <= ?";
        if ($search !== '') $countSql .= " AND (b.name LIKE ? OR f.batch_code LIKE ? OR e.remarks LIKE ?)";

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRows = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($totalRows / $perPage));

        $sql .= " ORDER BY e.prod_date DESC, e.id DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return View::render('eggs/index', [
            'pageTitle' => 'Egg Production & Collections',
            'activeNav' => 'eggs',
            'stats' => $stats,
            'records' => $records,
            'search' => $search,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows,
            'perPage' => $perPage,
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
        ]);
    }

    public function create(Request $request): Response
    {
        Auth::requirePermission('eggs', 'create');
        $pdo = DB::pdo();

        $breeds = $pdo->query("SELECT * FROM breeds ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $flocks = $pdo->query("SELECT flock_id, batch_code, breed_name FROM v_flock_status WHERE current_stock > 0 ORDER BY batch_code ASC")->fetchAll(PDO::FETCH_ASSOC);

        return View::render('eggs/create', [
            'pageTitle' => 'Record Daily Egg Production',
            'activeNav' => 'eggs',
            'breeds' => $breeds,
            'flocks' => $flocks,
        ]);
    }

    public function store(Request $request): Response
    {
        Auth::requirePermission('eggs', 'create');
        $pdo = DB::pdo();

        $prodDate = (string) $request->input('prod_date', date('Y-m-d'));
        $breedId = (int) $request->input('breed_id', 1);
        $flockId = (int) $request->input('flock_id', 0) ?: null;
        $birdsCount = (int) $request->input('birds_count', 0) ?: null;

        $collected = max(0, (int) $request->input('collected', 0));
        $broken = max(0, (int) $request->input('broken', 0));
        $consumed = max(0, (int) $request->input('consumed', 0));
        $sold = max(0, (int) $request->input('sold', 0));
        $incubated = max(0, (int) $request->input('incubated', 0));
        $kept = max(0, (int) $request->input('kept', 0));
        $remarks = trim((string) $request->input('remarks', ''));

        if ($collected <= 0) {
            $_SESSION['flash_error'] = "Eggs collected must be greater than zero.";
            return Response::redirect(url('/eggs/create'));
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO egg_production_daily (
                    breed_id, flock_id, prod_date, birds_count, collected,
                    broken, consumed, sold, incubated, kept, remarks
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    collected = VALUES(collected),
                    broken = VALUES(broken),
                    consumed = VALUES(consumed),
                    sold = VALUES(sold),
                    incubated = VALUES(incubated),
                    kept = VALUES(kept),
                    remarks = VALUES(remarks)
            ");
            $stmt->execute([
                $breedId, $flockId, $prodDate, $birdsCount, $collected,
                $broken, $consumed, $sold, $incubated, $kept, $remarks ?: null
            ]);
            $recordId = (int) $pdo->lastInsertId();

            Audit::log('create', 'egg_production_daily', $recordId, null, [
                'prod_date' => $prodDate,
                'breed_id' => $breedId,
                'flock_id' => $flockId,
                'collected' => $collected,
                'trays' => floor($collected / 30),
            ]);

            $trays = floor($collected / 30);
            $_SESSION['flash_success'] = "Recorded {$collected} eggs ({$trays} trays) for {$prodDate}.";
            return Response::redirect(url('/eggs'));
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Failed to record egg harvest: " . $e->getMessage();
            return Response::redirect(url('/eggs/create'));
        }
    }
}
