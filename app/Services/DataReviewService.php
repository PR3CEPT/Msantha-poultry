<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\DB;
use InvalidArgumentException;
use PDO;

class DataReviewService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? DB::pdo();
    }

    /**
     * Retrieve all suspect dates flagged during data import.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSuspectDates(int $limit = 100): array
    {
        $results = [];

        // 1. Flock Daily Legacy rows with date_suspect = 1
        $fStmt = $this->pdo->prepare("
            SELECT 
                fd.id, 'flock_daily_legacy' AS source_table, f.batch_code, f.legacy_sheet,
                fd.excel_row, fd.record_date, fd.date_suspect, fd.remarks,
                CONCAT('Flock Batch: ', f.batch_code, ' (Row ', fd.excel_row, ')') AS context
            FROM flock_daily_legacy fd
            JOIN flocks f ON f.id = fd.flock_id
            WHERE fd.date_suspect = 1
            ORDER BY fd.excel_row ASC
            LIMIT :lim
        ");
        $fStmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $fStmt->execute();
        while ($r = $fStmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = [
                'table' => 'flock_daily_legacy',
                'id' => (int) $r['id'],
                'context' => $r['context'],
                'sheet' => $r['legacy_sheet'] ?? $r['batch_code'],
                'excel_row' => (int) $r['excel_row'],
                'current_date' => $r['record_date'],
                'reason' => 'Date out of sequence or template year mix-up in Excel',
                'notes' => $r['remarks'],
            ];
        }

        // 2. Incubation Jobs with date_suspect = 1
        $iStmt = $this->pdo->prepare("
            SELECT 
                j.id, 'incubation_jobs' AS source_table, c.name AS customer_name,
                j.legacy_ref, j.date_received, j.raw_date, j.remarks
            FROM incubation_jobs j
            LEFT JOIN customers c ON c.id = j.customer_id
            WHERE j.date_suspect = 1
            LIMIT :lim
        ");
        $iStmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $iStmt->execute();
        while ($r = $iStmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = [
                'table' => 'incubation_jobs',
                'id' => (int) $r['id'],
                'context' => 'Incubation Job: ' . ($r['customer_name'] ?? 'Customer') . ' (' . ($r['legacy_ref'] ?? '') . ')',
                'sheet' => 'Poultry incubation analysis',
                'excel_row' => (int) preg_replace('/\D/', '', (string) $r['legacy_ref']),
                'current_date' => $r['date_received'],
                'reason' => 'Invalid or suspect date string: ' . ($r['raw_date'] ?? $r['remarks']),
                'notes' => $r['remarks'],
            ];
        }

        // 3. Cash entries with date_suspect = 1
        $cStmt = $this->pdo->prepare("
            SELECT 
                ce.id, 'cash_entries' AS source_table, bu.name AS bu_name,
                ce.entry_date, ce.raw_date, ce.description, ce.legacy_ref
            FROM cash_entries ce
            JOIN business_units bu ON bu.id = ce.business_unit_id
            WHERE ce.date_suspect = 1
            LIMIT :lim
        ");
        $cStmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $cStmt->execute();
        while ($r = $cStmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = [
                'table' => 'cash_entries',
                'id' => (int) $r['id'],
                'context' => 'Cash Book (' . $r['bu_name'] . '): ' . $r['description'],
                'sheet' => $r['bu_name'],
                'excel_row' => (int) preg_replace('/\D/', '', (string) $r['legacy_ref']),
                'current_date' => $r['entry_date'],
                'reason' => 'Suspect date text: ' . ($r['raw_date'] ?? ''),
                'notes' => $r['description'],
            ];
        }

        return $results;
    }

    /**
     * Retrieve rows with missing dates (date_missing = 1).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMissingDates(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                ce.id, 'cash_entries' AS source_table, bu.name AS bu_name,
                ce.entry_date, ce.description, ce.receipt, ce.expenditure, ce.legacy_ref
            FROM cash_entries ce
            JOIN business_units bu ON bu.id = ce.business_unit_id
            WHERE ce.date_missing = 1
            ORDER BY ce.id ASC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'table' => 'cash_entries',
                'id' => (int) $r['id'],
                'business_unit' => $r['bu_name'],
                'description' => $r['description'],
                'receipt' => (int) $r['receipt'],
                'expenditure' => (int) $r['expenditure'],
                'legacy_ref' => $r['legacy_ref'],
                'reason' => 'Excel row has text with missing transaction date',
            ];
        }

        return $rows;
    }

    /**
     * Retrieve pre-filled projected calendar rows past the import cut-off.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProjectedRows(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                fd.id, f.batch_code, f.legacy_sheet, fd.excel_row, fd.record_date,
                fd.opening_count, fd.deaths, fd.sold, fd.balance
            FROM flock_daily_legacy fd
            JOIN flocks f ON f.id = fd.flock_id
            WHERE fd.is_projected = 1
            ORDER BY fd.record_date ASC, fd.id ASC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve import exceptions & skipped rows summary.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSkippedRowsSummary(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, source_file, rows_in_csv, rows_imported, rows_skipped, skipped_detail, run_at
            FROM import_log
            WHERE rows_skipped > 0
            ORDER BY id ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resolve and fix a suspect or missing date row.
     */
    public function resolveDate(string $table, int $recordId, string $correctedDate, ?int $userId = null): bool
    {
        $validTables = ['flock_daily_legacy', 'incubation_jobs', 'cash_entries', 'egg_production_daily'];
        if (!in_array($table, $validTables, true)) {
            throw new InvalidArgumentException("Invalid table for date resolution: {$table}");
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $correctedDate)) {
            throw new InvalidArgumentException("Corrected date must be in YYYY-MM-DD format.");
        }

        $dateCol = match ($table) {
            'flock_daily_legacy' => 'record_date',
            'incubation_jobs' => 'date_received',
            'cash_entries' => 'entry_date',
            'egg_production_daily' => 'prod_date',
        };

        // Fetch old values
        $sel = $this->pdo->prepare("SELECT * FROM `{$table}` WHERE id = ? LIMIT 1");
        $sel->execute([$recordId]);
        $old = $sel->fetch(PDO::FETCH_ASSOC);
        if (!$old) {
            throw new InvalidArgumentException("Record #{$recordId} not found in {$table}");
        }

        if ($table === 'flock_daily_legacy') {
            $stmt = $this->pdo->prepare("UPDATE flock_daily_legacy SET record_date = ?, date_suspect = 0 WHERE id = ?");
            $stmt->execute([$correctedDate, $recordId]);
        } elseif ($table === 'cash_entries') {
            $stmt = $this->pdo->prepare("UPDATE cash_entries SET entry_date = ?, date_missing = 0, date_suspect = 0 WHERE id = ?");
            $stmt->execute([$correctedDate, $recordId]);
        } elseif ($table === 'incubation_jobs') {
            $stmt = $this->pdo->prepare("UPDATE incubation_jobs SET date_received = ?, date_suspect = 0 WHERE id = ?");
            $stmt->execute([$correctedDate, $recordId]);
        } elseif ($table === 'egg_production_daily') {
            $stmt = $this->pdo->prepare("UPDATE egg_production_daily SET prod_date = ?, date_suspect = 0 WHERE id = ?");
            $stmt->execute([$correctedDate, $recordId]);
        }

        Audit::log('resolve_date_quality', $table, $recordId, [
            $dateCol => $old[$dateCol] ?? null,
            'date_suspect' => $old['date_suspect'] ?? null,
            'date_missing' => $old['date_missing'] ?? null,
        ], [
            $dateCol => $correctedDate,
            'date_suspect' => 0,
            'date_missing' => 0,
        ], $userId);

        return true;
    }
}
