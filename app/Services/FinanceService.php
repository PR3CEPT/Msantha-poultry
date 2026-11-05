<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\DB;
use DomainException;
use InvalidArgumentException;
use PDO;

class FinanceService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? DB::pdo();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getBusinessUnits(): array
    {
        return $this->pdo->query("SELECT id, code, name, kind, is_active FROM business_units ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param int|string $idOrCode
     * @return array<string, mixed>|null
     */
    public function getBusinessUnit($idOrCode): ?array
    {
        if (is_numeric($idOrCode)) {
            $stmt = $this->pdo->prepare("SELECT * FROM business_units WHERE id = ? LIMIT 1");
            $stmt->execute([(int) $idOrCode]);
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM business_units WHERE code = ? LIMIT 1");
            $stmt->execute([(string) $idOrCode]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Check if a specific month is locked for a business unit.
     */
    public function isMonthLocked(int $businessUnitId, string $yearMonth): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT id FROM month_locks 
            WHERE business_unit_id = ? AND `year_month` = ? 
            LIMIT 1
        ");
        $stmt->execute([$businessUnitId, $yearMonth]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Lock a month-end for a business unit.
     */
    public function lockMonth(int $businessUnitId, string $yearMonth, ?int $userId = null, ?string $notes = null): void
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            throw new InvalidArgumentException("Invalid year_month format. Expected YYYY-MM.");
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO month_locks (business_unit_id, `year_month`, locked_by, locked_at, notes)
            VALUES (?, ?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE locked_by = VALUES(locked_by), locked_at = VALUES(locked_at), notes = VALUES(notes)
        ");
        $stmt->execute([$businessUnitId, $yearMonth, $userId, $notes]);

        Audit::log('lock', 'month_locks', null, null, [
            'business_unit_id' => $businessUnitId,
            'year_month' => $yearMonth,
            'locked_by' => $userId,
            'notes' => $notes,
        ]);
    }

    /**
     * Reopen (unlock) a month-end for a business unit.
     */
    public function unlockMonth(int $businessUnitId, string $yearMonth, ?int $userId = null): void
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM month_locks 
            WHERE business_unit_id = ? AND `year_month` = ?
        ");
        $stmt->execute([$businessUnitId, $yearMonth]);

        Audit::log('unlock', 'month_locks', null, [
            'business_unit_id' => $businessUnitId,
            'year_month' => $yearMonth,
        ], [
            'unlocked_by' => $userId,
        ]);
    }

    /**
     * Get all active month locks for a business unit (or all units).
     * @return array<string, array<string, mixed>> keyed by year_month
     */
    public function getMonthLocks(?int $businessUnitId = null): array
    {
        $sql = "
            SELECT ml.*, bu.code as unit_code, bu.name as unit_name, u.full_name as locked_by_name
            FROM month_locks ml
            JOIN business_units bu ON bu.id = ml.business_unit_id
            LEFT JOIN users u ON u.id = ml.locked_by
        ";
        $params = [];
        if ($businessUnitId !== null) {
            $sql .= " WHERE ml.business_unit_id = ?";
            $params[] = $businessUnitId;
        }
        $sql .= " ORDER BY ml.`year_month` DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $r) {
            $key = $r['business_unit_id'] . ':' . $r['year_month'];
            $result[$key] = $r;
        }
        return $result;
    }

    /**
     * Add a cash entry (receipt or expenditure).
     * Rejects if the month is locked.
     */
    public function addEntry(
        int $businessUnitId,
        string $entryDate,
        string $description,
        int $receipt,
        int $expenditure,
        ?int $categoryId = null,
        ?int $userId = null
    ): int {
        $cleanDesc = trim($description);
        if ($cleanDesc === '') {
            throw new InvalidArgumentException("Description is required.");
        }
        if ($receipt < 0 || $expenditure < 0) {
            throw new InvalidArgumentException("Receipt and expenditure amounts must be non-negative.");
        }
        if ($receipt === 0 && $expenditure === 0) {
            throw new InvalidArgumentException("Either receipt or expenditure must be greater than zero.");
        }

        // Validate date and format
        $timestamp = strtotime($entryDate);
        if ($timestamp === false) {
            throw new InvalidArgumentException("Invalid entry date: {$entryDate}");
        }
        $normDate = date('Y-m-d', $timestamp);
        $yearMonth = date('Y-m', $timestamp);

        // Check month lock
        if ($this->isMonthLocked($businessUnitId, $yearMonth)) {
            throw new DomainException("Cannot add transaction: Month {$yearMonth} is locked for editing.");
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO cash_entries (
                business_unit_id, entry_date, date_missing, description, receipt, expenditure, category_id, entry_kind, created_by
            ) VALUES (?, ?, 0, ?, ?, ?, ?, 'transaction', ?)
        ");
        $stmt->execute([
            $businessUnitId,
            $normDate,
            $cleanDesc,
            $receipt,
            $expenditure,
            $categoryId,
            $userId,
        ]);
        $entryId = (int) $this->pdo->lastInsertId();

        Audit::log('create', 'cash_entries', $entryId, null, [
            'business_unit_id' => $businessUnitId,
            'entry_date' => $normDate,
            'description' => $cleanDesc,
            'receipt' => $receipt,
            'expenditure' => $expenditure,
            'category_id' => $categoryId,
        ]);

        return $entryId;
    }

    /**
     * Get running cash balance for a business unit.
     */
    public function getRunningBalance(?int $businessUnitId = null): int
    {
        $sql = "
            SELECT COALESCE(SUM(receipt), 0) - COALESCE(SUM(expenditure), 0) AS balance
            FROM cash_entries
            WHERE entry_kind = 'transaction'
        ";
        $params = [];
        if ($businessUnitId !== null) {
            $sql .= " AND business_unit_id = ?";
            $params[] = $businessUnitId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get running bank balance from bank_movements.
     */
    public function getBankRunningBalance(): int
    {
        $stmt = $this->pdo->query("
            SELECT 
                COALESCE(SUM(CASE WHEN kind = 'deposit' THEN amount ELSE 0 END), 0) - 
                COALESCE(SUM(CASE WHEN kind = 'withdrawal' THEN amount ELSE 0 END), 0) AS bank_balance
            FROM bank_movements
        ");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Record a bank movement (deposit or withdrawal) and update running balance.
     */
    public function addBankMovement(string $moveDate, string $kind, int $amount, ?string $remarks = null): int
    {
        $kindLower = strtolower(trim($kind));
        if (!in_array($kindLower, ['deposit', 'withdrawal'], true)) {
            throw new InvalidArgumentException("Invalid movement kind. Must be 'deposit' or 'withdrawal'.");
        }
        if ($amount <= 0) {
            throw new InvalidArgumentException("Movement amount must be greater than zero.");
        }
        $timestamp = strtotime($moveDate);
        if ($timestamp === false) {
            throw new InvalidArgumentException("Invalid movement date: {$moveDate}");
        }
        $normDate = date('Y-m-d', $timestamp);

        $currentBal = $this->getBankRunningBalance();
        $balanceAfter = ($kindLower === 'deposit') ? ($currentBal + $amount) : ($currentBal - $amount);

        $stmt = $this->pdo->prepare("
            INSERT INTO bank_movements (move_date, kind, amount, balance_after, remarks)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $normDate,
            $kindLower,
            $amount,
            $balanceAfter,
            $remarks ? trim($remarks) : null,
        ]);
        $id = (int) $this->pdo->lastInsertId();

        Audit::log('create', 'bank_movements', $id, null, [
            'move_date' => $normDate,
            'kind' => $kindLower,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'remarks' => $remarks,
        ]);

        return $id;
    }

    /**
     * Get bank movements list with pagination.
     * @return array<int, array<string, mixed>>
     */
    public function getBankMovements(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM bank_movements 
            ORDER BY move_date DESC, id DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get Profit and Loss per business unit per month.
     * Integrates cash entries and incubation allocations for POULTRY.
     * @return array<string, mixed>
     */
    public function getProfitAndLoss(?int $year = null): array
    {
        $yearWhere = "";
        $params = [];
        if ($year !== null) {
            $yearWhere = " AND (entry_date IS NULL OR YEAR(entry_date) = ?)";
            $params[] = $year;
        }

        // Fetch monthly cash receipts and expenditures per business unit
        $sql = "
            SELECT 
                bu.id AS business_unit_id,
                bu.code AS unit_code,
                bu.name AS unit_name,
                COALESCE(DATE_FORMAT(ce.entry_date, '%Y-%m'), 'Pre-dated') AS month_key,
                SUM(ce.receipt) AS total_receipts,
                SUM(ce.expenditure) AS total_expenses
            FROM business_units bu
            LEFT JOIN cash_entries ce ON ce.business_unit_id = bu.id AND ce.entry_kind = 'transaction' {$yearWhere}
            GROUP BY bu.id, month_key
            ORDER BY bu.id ASC, month_key DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch incubation allocation totals for POULTRY
        $incSql = "
            SELECT 
                DATE_FORMAT(ip.paid_on, '%Y-%m') AS month_key,
                SUM(CASE WHEN ia.category = 'electricity' THEN ia.amount ELSE 0 END) AS electricity,
                SUM(CASE WHEN ia.category = 'water' THEN ia.amount ELSE 0 END) AS water,
                SUM(CASE WHEN ia.category = 'labour' THEN ia.amount ELSE 0 END) AS labour,
                SUM(CASE WHEN ia.category = 'telephone' THEN ia.amount ELSE 0 END) AS telephone,
                SUM(CASE WHEN ia.category = 'profit' THEN ia.amount ELSE 0 END) AS profit,
                SUM(ia.amount) AS total_allocated
            FROM incubation_allocations ia
            JOIN incubation_payments ip ON ip.id = ia.payment_id
        ";
        if ($year !== null) {
            $incSql .= " WHERE YEAR(ip.paid_on) = ?";
            $incStmt = $this->pdo->prepare($incSql . " GROUP BY month_key ORDER BY month_key DESC");
            $incStmt->execute([$year]);
        } else {
            $incStmt = $this->pdo->query($incSql . " GROUP BY month_key ORDER BY month_key DESC");
        }
        $incRows = $incStmt->fetchAll(PDO::FETCH_ASSOC);
        $incMap = [];
        foreach ($incRows as $ir) {
            if (!empty($ir['month_key'])) {
                $incMap[$ir['month_key']] = $ir;
            }
        }

        // Summary cards per business unit
        $unitCards = [];
        $units = $this->getBusinessUnits();
        foreach ($units as $u) {
            $unitCards[$u['code']] = [
                'id' => $u['id'],
                'code' => $u['code'],
                'name' => $u['name'],
                'kind' => $u['kind'],
                'receipts' => 0,
                'expenses' => 0,
                'net' => 0,
            ];
        }

        foreach ($rows as $r) {
            $code = $r['unit_code'];
            if (isset($unitCards[$code])) {
                $unitCards[$code]['receipts'] += (int) $r['total_receipts'];
                $unitCards[$code]['expenses'] += (int) $r['total_expenses'];
                $unitCards[$code]['net'] = $unitCards[$code]['receipts'] - $unitCards[$code]['expenses'];
            }
        }

        return [
            'monthly_rows' => $rows,
            'incubation_allocations' => $incMap,
            'unit_cards' => $unitCards,
        ];
    }

    /**
     * Generates reconciliation table: compares Excel 'month_total' rows with sum of transactions.
     * @return array<int, array<string, mixed>>
     */
    public function reconcileMonthTotals(int $businessUnitId): array
    {
        $rows = $this->pdo->prepare("
            SELECT id, entry_date, description, receipt, expenditure, entry_kind, legacy_ref
            FROM cash_entries
            WHERE business_unit_id = ?
            ORDER BY id ASC
        ");
        $rows->execute([$businessUnitId]);
        $all = $rows->fetchAll(PDO::FETCH_ASSOC);

        $reconciliation = [];
        $runningRec = 0;
        $runningExp = 0;
        $blockCount = 0;

        foreach ($all as $item) {
            if ($item['entry_kind'] === 'transaction') {
                $runningRec += (int) $item['receipt'];
                $runningExp += (int) $item['expenditure'];
            } elseif ($item['entry_kind'] === 'month_total') {
                $blockCount++;
                $excelRec = (int) $item['receipt'];
                $excelExp = (int) $item['expenditure'];
                $recDiff = $runningRec - $excelRec;
                $expDiff = $runningExp - $excelExp;
                $isMatch = ($recDiff === 0 && $expDiff === 0);

                $reconciliation[] = [
                    'block' => $blockCount,
                    'legacy_ref' => $item['legacy_ref'],
                    'excel_receipt' => $excelRec,
                    'excel_expenditure' => $excelExp,
                    'system_receipt' => $runningRec,
                    'system_expenditure' => $runningExp,
                    'diff_receipt' => $recDiff,
                    'diff_expenditure' => $expDiff,
                    'is_reconciled' => $isMatch,
                ];

                // reset block
                $runningRec = 0;
                $runningExp = 0;
            }
        }

        return $reconciliation;
    }

    /**
     * Export cashbook entries as CSV string.
     */
    public function exportCsv(int $businessUnitId, ?string $yearMonth = null): string
    {
        $bu = $this->getBusinessUnit($businessUnitId);
        $buName = $bu ? $bu['name'] : 'Business Unit';

        $sql = "
            SELECT c.*, ec.name AS category_name
            FROM cash_entries c
            LEFT JOIN expense_categories ec ON ec.id = c.category_id
            WHERE c.business_unit_id = ?
        ";
        $params = [$businessUnitId];
        if ($yearMonth !== null && $yearMonth !== '') {
            $sql .= " AND DATE_FORMAT(c.entry_date, '%Y-%m') = ?";
            $params[] = $yearMonth;
        }
        $sql .= " ORDER BY c.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $output = fopen('php://temp', 'r+');
        if (!$output) {
            return '';
        }

        // Header
        fputcsv($output, ['ID', 'Date', 'Business Unit', 'Description', 'Category', 'Kind', 'Receipt (MWK)', 'Expenditure (MWK)', 'Running Balance (MWK)', 'Legacy Ref']);

        $balance = 0;
        foreach ($rows as $r) {
            $rec = (int) $r['receipt'];
            $exp = (int) $r['expenditure'];
            if ($r['entry_kind'] === 'transaction') {
                $balance += ($rec - $exp);
            }
            fputcsv($output, [
                $r['id'],
                $r['entry_date'] ?: 'Missing Date',
                $buName,
                $r['description'],
                $r['category_name'] ?: 'General',
                $r['entry_kind'],
                $rec,
                $exp,
                $balance,
                $r['legacy_ref'] ?: '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv ?: '';
    }
}
