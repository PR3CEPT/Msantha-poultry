<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\FinanceService;
use DomainException;
use InvalidArgumentException;
use PDO;

class FinanceController
{
    private FinanceService $financeService;

    public function __construct(?FinanceService $financeService = null)
    {
        $this->financeService = $financeService ?? new FinanceService();
    }

    public function index(Request $request): Response
    {
        Auth::requirePermission('finance', 'view');
        Audit::logView('finance');
        $pdo = DB::pdo();

        $selectedUnit = trim((string) $request->query('unit', 'POULTRY'));
        $kind = trim((string) $request->query('kind', 'all'));
        $search = trim((string) $request->query('q', ''));
        $yearMonth = trim((string) $request->query('month', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;

        // Fetch business units
        $units = $this->financeService->getBusinessUnits();

        // Find current unit ID if not 'all'
        $activeUnit = null;
        if ($selectedUnit !== 'all') {
            $activeUnit = $this->financeService->getBusinessUnit($selectedUnit);
            if (!$activeUnit && !empty($units)) {
                $activeUnit = $units[0];
                $selectedUnit = $activeUnit['code'];
            }
        }

        // Running balance for active unit and overall bank balance
        $unitBalance = $activeUnit ? $this->financeService->getRunningBalance((int) $activeUnit['id']) : $this->financeService->getRunningBalance(null);
        $bankBalance = $this->financeService->getBankRunningBalance();

        // Month lock status for selected unit & month
        $isCurrentMonthLocked = false;
        if ($activeUnit && $yearMonth !== '') {
            $isCurrentMonthLocked = $this->financeService->isMonthLocked((int) $activeUnit['id'], $yearMonth);
        }

        // Stats
        $statWhere = " WHERE entry_kind = 'transaction'";
        $statParams = [];
        if ($activeUnit) {
            $statWhere .= " AND c.business_unit_id = ?";
            $statParams[] = (int) $activeUnit['id'];
        }
        if ($yearMonth !== '') {
            $statWhere .= " AND DATE_FORMAT(c.entry_date, '%Y-%m') = ?";
            $statParams[] = $yearMonth;
        }
        if ($dateFrom !== '') {
            $statWhere .= " AND c.entry_date >= ?";
            $statParams[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $statWhere .= " AND c.entry_date <= ?";
            $statParams[] = $dateTo;
        }

        $summaryStmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(c.receipt), 0) AS total_receipts,
                COALESCE(SUM(c.expenditure), 0) AS total_expenses
            FROM cash_entries c
            {$statWhere}
        ");
        $summaryStmt->execute($statParams);
        $summaryRow = $summaryStmt->fetch(PDO::FETCH_ASSOC);

        $stats = [
            'total_receipts' => $summaryRow ? (int) $summaryRow['total_receipts'] : 0,
            'total_expenses' => $summaryRow ? (int) $summaryRow['total_expenses'] : 0,
            'net_balance' => $unitBalance,
            'bank_balance' => $bankBalance,
            'is_locked' => $isCurrentMonthLocked,
        ];

        // Query entries
        $whereSql = " WHERE 1=1";
        $params = [];

        if ($activeUnit) {
            $whereSql .= " AND c.business_unit_id = ?";
            $params[] = (int) $activeUnit['id'];
        }

        if ($kind !== 'all' && $kind !== '') {
            $whereSql .= " AND c.entry_kind = ?";
            $params[] = $kind;
        }

        if ($yearMonth !== '') {
            $whereSql .= " AND DATE_FORMAT(c.entry_date, '%Y-%m') = ?";
            $params[] = $yearMonth;
        }

        if ($dateFrom !== '') {
            $whereSql .= " AND c.entry_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $whereSql .= " AND c.entry_date <= ?";
            $params[] = $dateTo;
        }

        if ($search !== '') {
            $whereSql .= " AND (c.description LIKE ? OR c.legacy_ref LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $countStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM cash_entries c
            {$whereSql}
        ");
        $countStmt->execute($params);
        $totalRows = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($totalRows / $perPage));

        $sql = "
            SELECT c.*, bu.code AS unit_code, bu.name AS unit_name, ec.name AS category_name
            FROM cash_entries c
            JOIN business_units bu ON bu.id = c.business_unit_id
            LEFT JOIN expense_categories ec ON ec.id = c.category_id
            {$whereSql}
            ORDER BY c.entry_date DESC, c.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch categories for form
        $categories = $pdo->query("SELECT id, name FROM expense_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch recent bank movements
        $bankMovements = $this->financeService->getBankMovements(10);

        // Fetch month locks for selected unit
        $activeUnitLocks = $activeUnit ? $this->financeService->getMonthLocks((int) $activeUnit['id']) : [];

        return View::render('finance/index', [
            'pageTitle' => 'Financial Cash Books',
            'activeNav' => 'finance',
            'stats' => $stats,
            'entries' => $entries,
            'units' => $units,
            'activeUnit' => $activeUnit,
            'selectedUnit' => $selectedUnit,
            'kind' => $kind,
            'search' => $search,
            'yearMonth' => $yearMonth,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows,
            'categories' => $categories,
            'bankMovements' => $bankMovements,
            'activeUnitLocks' => $activeUnitLocks,
        ]);
    }

    public function storeEntry(Request $request): Response
    {
        Auth::requirePermission('finance', 'create');

        $buId = (int) $request->input('business_unit_id', 1);
        $entryDate = trim((string) $request->input('entry_date', date('Y-m-d')));
        $description = trim((string) $request->input('description', ''));
        $receipt = (int) $request->input('receipt', 0);
        $expenditure = (int) $request->input('expenditure', 0);
        $categoryId = !empty($request->input('category_id')) ? (int) $request->input('category_id') : null;
        $userId = Auth::id();

        try {
            $this->financeService->addEntry(
                $buId,
                $entryDate,
                $description,
                $receipt,
                $expenditure,
                $categoryId,
                $userId
            );

            $_SESSION['flash_success'] = "Cash entry recorded successfully.";
        } catch (DomainException $de) {
            $_SESSION['flash_error'] = $de->getMessage();
        } catch (InvalidArgumentException $ie) {
            $_SESSION['flash_error'] = $ie->getMessage();
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = "Failed to record cash entry: " . $e->getMessage();
        }

        $bu = $this->financeService->getBusinessUnit($buId);
        $unitCode = $bu ? $bu['code'] : 'all';
        return Response::redirect(url('/finance?unit=' . urlencode($unitCode)));
    }

    public function lockMonth(Request $request): Response
    {
        Auth::requirePermission('finance', 'update');

        $buId = (int) $request->input('business_unit_id');
        $yearMonth = trim((string) $request->input('year_month'));
        $notes = trim((string) $request->input('notes', 'Month closed by accounting'));
        $userId = Auth::id() ?? 1;

        if ($buId <= 0 || !preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            $_SESSION['flash_error'] = "Invalid business unit or month format (YYYY-MM).";
            return Response::redirect(url('/finance'));
        }

        try {
            $this->financeService->lockMonth($buId, $yearMonth, $userId, $notes);
            $_SESSION['flash_success'] = "Month {$yearMonth} has been locked successfully. No further edits permitted.";
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = "Failed to lock month: " . $e->getMessage();
        }

        $bu = $this->financeService->getBusinessUnit($buId);
        $unitCode = $bu ? $bu['code'] : 'all';
        return Response::redirect(url('/finance?unit=' . urlencode($unitCode) . '&month=' . urlencode($yearMonth)));
    }

    public function unlockMonth(Request $request): Response
    {
        // Reopening a month is strictly restricted to Owner
        Auth::requireRole('owner');

        $buId = (int) $request->input('business_unit_id');
        $yearMonth = trim((string) $request->input('year_month'));
        $userId = Auth::id() ?? 1;

        if ($buId <= 0 || !preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            $_SESSION['flash_error'] = "Invalid business unit or month format.";
            return Response::redirect(url('/finance'));
        }

        try {
            $this->financeService->unlockMonth($buId, $yearMonth, $userId);
            $_SESSION['flash_success'] = "Month {$yearMonth} has been reopened by Owner.";
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = "Failed to unlock month: " . $e->getMessage();
        }

        $bu = $this->financeService->getBusinessUnit($buId);
        $unitCode = $bu ? $bu['code'] : 'all';
        return Response::redirect(url('/finance?unit=' . urlencode($unitCode) . '&month=' . urlencode($yearMonth)));
    }

    public function storeBankMovement(Request $request): Response
    {
        Auth::requirePermission('finance', 'create');

        $moveDate = trim((string) $request->input('move_date', date('Y-m-d')));
        $kind = trim((string) $request->input('kind', 'deposit'));
        $amount = (int) $request->input('amount', 0);
        $remarks = trim((string) $request->input('remarks', ''));

        try {
            $this->financeService->addBankMovement($moveDate, $kind, $amount, $remarks);
            $_SESSION['flash_success'] = "Bank " . htmlspecialchars($kind) . " of MK " . number_format($amount) . " recorded.";
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = "Failed to record bank movement: " . $e->getMessage();
        }

        return Response::redirect(url('/finance'));
    }

    public function profitAndLoss(Request $request): Response
    {
        Auth::requirePermission('finance', 'view');
        Audit::logView('profit_and_loss');

        $year = (int) $request->query('year', (int) date('Y'));
        $pnlData = $this->financeService->getProfitAndLoss($year);
        $units = $this->financeService->getBusinessUnits();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $monthlyRows = $pnlData['monthly_rows'] ?? [];
        $totalRows = count($monthlyRows);
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        $pnlData['monthly_rows'] = array_slice($monthlyRows, ($page - 1) * $perPage, $perPage);

        return View::render('finance/pnl', [
            'pageTitle' => 'Profit & Loss Statement (' . $year . ')',
            'activeNav' => 'finance',
            'year' => $year,
            'pnl' => $pnlData,
            'units' => $units,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows,
            'perPage' => $perPage,
        ]);
    }

    public function exportCsv(Request $request): Response
    {
        Auth::requirePermission('finance', 'view');

        $buId = (int) $request->query('business_unit_id', 1);
        $yearMonth = trim((string) $request->query('month', ''));
        $bu = $this->financeService->getBusinessUnit($buId);
        $unitCode = $bu ? $bu['code'] : 'finance';

        $csv = $this->financeService->exportCsv($buId, $yearMonth !== '' ? $yearMonth : null);
        $filename = "cashbook_{$unitCode}_" . ($yearMonth ?: 'all') . ".csv";

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function reconcile(Request $request): Response
    {
        Auth::requirePermission('finance', 'view');

        $buId = (int) $request->query('business_unit_id', 1);
        $bu = $this->financeService->getBusinessUnit($buId);
        $units = $this->financeService->getBusinessUnits();
        $reconciliation = $this->financeService->reconcileMonthTotals($buId);

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $totalRows = count($reconciliation);
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        $paginatedReconciliation = array_slice($reconciliation, ($page - 1) * $perPage, $perPage);

        return View::render('finance/reconciliation', [
            'pageTitle' => 'Month-End Reconciliation',
            'activeNav' => 'finance',
            'bu' => $bu,
            'units' => $units,
            'reconciliation' => $paginatedReconciliation,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows,
            'perPage' => $perPage,
        ]);
    }
}
