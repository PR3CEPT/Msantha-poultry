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

class FeedDrugController
{
    public function index(Request $request): Response
    {
        Auth::requirePermission('feed_drugs', 'view');
        $pdo = DB::pdo();

        $tab = $request->query('tab', 'feed');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        // Fetch feed types
        $feedTypes = $pdo->query("SELECT * FROM feed_types ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        if ($tab === 'drugs') {
            $totalRows = (int) $pdo->query("SELECT COUNT(*) FROM drug_purchases")->fetchColumn();
            $drugPurchases = $pdo->query("
                SELECT * FROM drug_purchases
                ORDER BY purchase_date DESC, id DESC
                LIMIT $perPage OFFSET $offset
            ")->fetchAll(PDO::FETCH_ASSOC);
            $feedPurchases = [];
        } else {
            $totalRows = (int) $pdo->query("SELECT COUNT(*) FROM feed_purchases")->fetchColumn();
            $feedPurchases = $pdo->query("
                SELECT fp.*, ft.name AS feed_type_name
                FROM feed_purchases fp
                JOIN feed_types ft ON ft.id = fp.feed_type_id
                ORDER BY fp.purchase_date DESC, fp.id DESC
                LIMIT $perPage OFFSET $offset
            ")->fetchAll(PDO::FETCH_ASSOC);
            $drugPurchases = [];
        }
        $totalPages = max(1, (int) ceil($totalRows / $perPage));

        // Overall statistics
        $stats = [
            'total_feed_cost' => 0,
            'total_feed_kg' => 0.0,
            'total_drug_cost' => 0,
            'cost_per_bird' => 0,
            'cost_per_egg' => 0,
        ];

        $fSum = $pdo->query("SELECT COALESCE(SUM(cost), 0) AS total_cost, COALESCE(SUM(quantity_kg), 0) AS total_kg FROM feed_purchases")->fetch(PDO::FETCH_ASSOC);
        if ($fSum) {
            $stats['total_feed_cost'] = (int) $fSum['total_cost'];
            $stats['total_feed_kg'] = (float) $fSum['total_kg'];
        }

        $stats['total_drug_cost'] = (int) $pdo->query("SELECT COALESCE(SUM(cost), 0) FROM drug_purchases")->fetchColumn();

        // Feed cost per bird / egg report calculation
        $totalBirdsStocked = (int) $pdo->query("SELECT COALESCE(SUM(start_count), 0) FROM flocks")->fetchColumn();
        $totalEggs = (int) $pdo->query("SELECT COALESCE(SUM(collected), 0) FROM egg_production_daily")->fetchColumn();

        if ($totalBirdsStocked > 0) {
            $stats['cost_per_bird'] = (int) round($stats['total_feed_cost'] / $totalBirdsStocked);
        }
        if ($totalEggs > 0) {
            $stats['cost_per_egg'] = (int) round($stats['total_feed_cost'] / $totalEggs);
        }

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return View::render('feed_drugs/index', [
            'pageTitle' => 'Feed & Drugs Inventory & Cost Report',
            'activeNav' => 'feed_drugs',
            'tab' => $tab,
            'stats' => $stats,
            'feedTypes' => $feedTypes,
            'feedPurchases' => $feedPurchases,
            'drugPurchases' => $drugPurchases,
            'totalBirdsStocked' => $totalBirdsStocked,
            'totalEggs' => $totalEggs,
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows,
            'perPage' => $perPage,
        ]);
    }

    public function storeFeed(Request $request): Response
    {
        Auth::requirePermission('feed_drugs', 'create');
        $pdo = DB::pdo();

        $purchaseDate = (string) $request->input('purchase_date', date('Y-m-d'));
        $feedTypeId = (int) $request->input('feed_type_id', 1);
        $quantityKg = (float) $request->input('quantity_kg', 0);
        $cost = (int) $request->input('cost', 0);
        $supplier = trim((string) $request->input('supplier', ''));

        if ($quantityKg <= 0 || $cost <= 0) {
            $_SESSION['flash_error'] = "Quantity (kg) and cost (MWK) must be positive values.";
            return Response::redirect(url('/feed-drugs?tab=feed'));
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO feed_purchases (purchase_date, feed_type_id, quantity_kg, cost, supplier)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$purchaseDate, $feedTypeId, $quantityKg, $cost, $supplier ?: null]);
            $newId = (int) $pdo->lastInsertId();

            Audit::log('create', 'feed_purchases', $newId, null, [
                'purchase_date' => $purchaseDate,
                'feed_type_id' => $feedTypeId,
                'quantity_kg' => $quantityKg,
                'cost' => $cost,
            ]);

            $_SESSION['flash_success'] = "Feed purchase recorded: {$quantityKg} kg for " . number_format($cost) . " MWK.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Failed to record feed: " . $e->getMessage();
        }

        return Response::redirect(url('/feed-drugs?tab=feed'));
    }

    public function storeDrug(Request $request): Response
    {
        Auth::requirePermission('feed_drugs', 'create');
        $pdo = DB::pdo();

        $purchaseDate = (string) $request->input('purchase_date', date('Y-m-d'));
        $item = trim((string) $request->input('item', ''));
        $doses = (int) $request->input('doses', 0) ?: null;
        $cost = (int) $request->input('cost', 0);

        if ($item === '' || $cost <= 0) {
            $_SESSION['flash_error'] = "Drug/vaccine name and cost (MWK) are required.";
            return Response::redirect(url('/feed-drugs?tab=drugs'));
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO drug_purchases (purchase_date, item, doses, cost)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$purchaseDate, $item, $doses, $cost]);
            $newId = (int) $pdo->lastInsertId();

            Audit::log('create', 'drug_purchases', $newId, null, [
                'purchase_date' => $purchaseDate,
                'item' => $item,
                'doses' => $doses,
                'cost' => $cost,
            ]);

            $_SESSION['flash_success'] = "Drug purchase recorded: '{$item}' for " . number_format($cost) . " MWK.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Failed to record drug: " . $e->getMessage();
        }

        return Response::redirect(url('/feed-drugs?tab=drugs'));
    }
}

