<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

class SaleController
{
    public function index(Request $request): Response
    {
        Auth::requirePermission('sales', 'view');
        $pdo = DB::pdo();

        $search = trim((string) $request->query('q', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        // Overall stats
        $stats = [
            'total_sales_mwk' => 0,
            'total_paid_mwk' => 0,
            'total_debtors_mwk' => 0,
            'count_sales' => 0,
        ];

        $summaryRow = $pdo->query("
            SELECT 
                COUNT(*) AS count_sales,
                COALESCE(SUM(total), 0) AS total_sales,
                COALESCE(SUM(paid), 0) AS total_paid,
                COALESCE(SUM(total - paid), 0) AS total_debtors
            FROM sales
        ")->fetch(PDO::FETCH_ASSOC);

        if ($summaryRow) {
            $stats['count_sales'] = (int) $summaryRow['count_sales'];
            $stats['total_sales_mwk'] = (int) $summaryRow['total_sales'];
            $stats['total_paid_mwk'] = (int) $summaryRow['total_paid'];
            $stats['total_debtors_mwk'] = max(0, (int) $summaryRow['total_debtors']);
        }

        $whereSql = " WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $whereSql .= " AND (c.name LIKE ? OR s.legacy_ref LIKE ? OR s.payment_method LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($dateFrom !== '') {
            $whereSql .= " AND s.sale_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $whereSql .= " AND s.sale_date <= ?";
            $params[] = $dateTo;
        }

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM sales s LEFT JOIN customers c ON c.id = s.customer_id {$whereSql}");
        $countStmt->execute($params);
        $totalRows = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($totalRows / $perPage));

        $sql = "
            SELECT s.*, c.name AS customer_name, c.phone AS customer_phone,
                   COUNT(si.id) AS items_count
            FROM sales s
            LEFT JOIN customers c ON c.id = s.customer_id
            LEFT JOIN sale_items si ON si.sale_id = s.id
            {$whereSql}
            GROUP BY s.id
            ORDER BY s.sale_date DESC, s.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        return View::render('sales/index', [
            'pageTitle' => 'Sales & Commercial Offloads',
            'activeNav' => 'sales',
            'stats' => $stats,
            'sales' => $sales,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows,
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
        ]);
    }

    public function create(Request $request): Response
    {
        Auth::requirePermission('sales', 'create');
        $pdo = DB::pdo();

        $customers = $pdo->query("SELECT id, name, phone FROM customers ORDER BY name ASC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
        $breeds = $pdo->query("SELECT * FROM breeds ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $flocks = $pdo->query("SELECT flock_id, batch_code, breed_name, current_stock FROM v_flock_status WHERE current_stock > 0 ORDER BY batch_code ASC")->fetchAll(PDO::FETCH_ASSOC);

        return View::render('sales/create', [
            'pageTitle' => 'Record Commercial Sale',
            'activeNav' => 'sales',
            'customers' => $customers,
            'breeds' => $breeds,
            'flocks' => $flocks,
        ]);
    }

    public function store(Request $request): Response
    {
        Auth::requirePermission('sales', 'create');
        $pdo = DB::pdo();

        $saleDate = (string) $request->input('sale_date', date('Y-m-d'));
        $customerId = (int) $request->input('customer_id', 0) ?: null;
        $paymentMethod = (string) $request->input('payment_method', 'cash');
        $itemTypes = (array) $request->input('item_type', []);
        $quantities = (array) $request->input('quantity', []);
        $unitPrices = (array) $request->input('unit_price', []);
        $breedIds = (array) $request->input('breed_id', []);
        $flockIds = (array) $request->input('flock_id', []);

        if (empty($itemTypes)) {
            $_SESSION['flash_error'] = "At least one sale line item is required.";
            return Response::redirect(url('/sales/create'));
        }

        $items = [];
        $total = 0;

        foreach ($itemTypes as $i => $type) {
            $qty = max(1, (int) ($quantities[$i] ?? 1));
            $unitPrice = max(0, (int) ($unitPrices[$i] ?? 0));
            $lineAmt = $qty * $unitPrice;
            $bId = (int) ($breedIds[$i] ?? 0) ?: null;
            $fId = (int) ($flockIds[$i] ?? 0) ?: null;

            // Stock validation for live birds
            if ($type === 'live_birds' && $fId) {
                $stockStmt = $pdo->prepare("SELECT current_stock, batch_code FROM v_flock_status WHERE flock_id = ?");
                $stockStmt->execute([$fId]);
                $flockInfo = $stockStmt->fetch(PDO::FETCH_ASSOC);

                if (!$flockInfo) {
                    $_SESSION['flash_error'] = "Selected flock batch does not exist.";
                    return Response::redirect(url('/sales/create'));
                }

                if ((int) $flockInfo['current_stock'] < $qty) {
                    $_SESSION['flash_error'] = "Cannot sell {$qty} birds from batch '{$flockInfo['batch_code']}': only {$flockInfo['current_stock']} birds available in stock.";
                    return Response::redirect(url('/sales/create'));
                }
            }

            $items[] = [
                'item_type' => $type,
                'breed_id' => $bId,
                'flock_id' => $fId,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'amount' => $lineAmt,
            ];
            $total += $lineAmt;
        }

        // Credit sales: paid = 0, creates debtor receivable; other payment methods: paid = total
        $isCredit = ($paymentMethod === 'credit');
        $paid = $isCredit ? 0 : $total;

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO sales (sale_date, customer_id, total, paid, payment_method, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$saleDate, $customerId, $total, $paid, $paymentMethod, Auth::id()]);
            $saleId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare("
                INSERT INTO sale_items (sale_id, item_type, breed_id, flock_id, quantity, unit_price, amount)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $eventStmt = $pdo->prepare("
                INSERT INTO flock_events (flock_id, event_date, event_type, quantity, unit_price, amount, customer_id, remarks, created_by, created_at)
                VALUES (?, ?, 'sale', ?, ?, ?, ?, ?, ?, NOW())
            ");

            foreach ($items as $item) {
                $itemStmt->execute([
                    $saleId,
                    $item['item_type'],
                    $item['breed_id'],
                    $item['flock_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['amount'],
                ]);

                // If live birds sold from a specific flock, record authoritative deduction event
                if ($item['item_type'] === 'live_birds' && !empty($item['flock_id'])) {
                    $eventStmt->execute([
                        $item['flock_id'],
                        $saleDate,
                        $item['quantity'],
                        $item['unit_price'],
                        $item['amount'],
                        $customerId,
                        "Commercial Sale #{$saleId}",
                        Auth::id(),
                    ]);
                }
            }

            $pdo->commit();

            Audit::log('create', 'sales', $saleId, null, [
                'sale_date' => $saleDate,
                'customer_id' => $customerId,
                'total' => $total,
                'payment_method' => $paymentMethod,
                'is_credit' => $isCredit,
            ]);

            $_SESSION['flash_success'] = "Sale #{$saleId} recorded for " . number_format($total) . " MWK (" . ($isCredit ? "Debtor Credit Sale" : "Settled") . "). Batch stock deducted accordingly.";
            return Response::redirect(url("/sales/{$saleId}/receipt"));
        } catch (Throwable $e) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = "Sale failed: " . $e->getMessage();
            return Response::redirect(url('/sales/create'));
        }
    }

    public function receipt(Request $request, int|string $id): Response
    {
        $id = (int) $id;
        Auth::requirePermission('sales', 'view');
        $pdo = DB::pdo();

        $stmt = $pdo->prepare("
            SELECT s.*, c.name AS customer_name, c.phone AS customer_phone, c.location AS customer_location
            FROM sales s
            LEFT JOIN customers c ON c.id = s.customer_id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sale) {
            return Response::html("<h1>Sale not found</h1>", 404);
        }

        $itemsStmt = $pdo->prepare("
            SELECT si.*, b.name AS breed_name, f.batch_code
            FROM sale_items si
            LEFT JOIN breeds b ON b.id = si.breed_id
            LEFT JOIN flocks f ON f.id = si.flock_id
            WHERE si.sale_id = ?
        ");
        $itemsStmt->execute([$id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        return View::render('sales/receipt', [
            'pageTitle' => "Sale Receipt #{$sale['id']}",
            'sale' => $sale,
            'items' => $items,
        ]);
    }
}

