<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

class AuditController
{
    public function index(Request $request): Response
    {
        Auth::requirePermission('admin_audit', 'view');
        Audit::logView('admin_audit');

        $pdo = DB::pdo();

        $actionFilter = trim((string) $request->input('action', ''));
        $tableFilter = trim((string) $request->input('table', ''));
        $userFilter = trim((string) $request->input('user_id', ''));
        $dateFrom = trim((string) $request->input('date_from', ''));
        $dateTo = trim((string) $request->input('date_to', ''));

        // Pagination
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        // Sort whitelist
        $sortColumn = (string) $request->input('sort', 'created_at');
        $allowedSorts = ['created_at', 'action', 'table_name', 'id'];
        if (!in_array($sortColumn, $allowedSorts, true)) {
            $sortColumn = 'created_at';
        }

        $sortOrder = strtoupper((string) $request->input('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $where = ['1=1'];
        $params = [];

        if ($actionFilter !== '') {
            $where[] = 'a.action = :action';
            $params['action'] = $actionFilter;
        }

        if ($tableFilter !== '') {
            $where[] = 'a.table_name = :table_name';
            $params['table_name'] = $tableFilter;
        }

        if ($userFilter !== '') {
            $where[] = 'a.user_id = :user_id';
            $params['user_id'] = (int) $userFilter;
        }

        if ($dateFrom !== '') {
            $where[] = 'a.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== '') {
            $where[] = 'a.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        // Count total matching
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log a WHERE {$whereClause}");
        $countStmt->execute($params);
        $totalRecords = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($totalRecords / $perPage));

        // Fetch page rows
        $sql = "
            SELECT a.*, u.username, u.full_name, r.name AS role_name
            FROM audit_log a
            LEFT JOIN users u ON u.id = a.user_id
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE {$whereClause}
            ORDER BY a.{$sortColumn} {$sortOrder}
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // Get unique tables & users for filters
        $usersList = $pdo->query("SELECT id, username, full_name FROM users ORDER BY username ASC")->fetchAll();
        $tablesList = $pdo->query("SELECT DISTINCT table_name FROM audit_log WHERE table_name IS NOT NULL ORDER BY table_name ASC")->fetchAll(\PDO::FETCH_COLUMN);

        return View::render('admin/audit/index', [
            'pageTitle' => 'Audit Log',
            'logs' => $logs,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'usersList' => $usersList,
            'tablesList' => $tablesList,
            'actionFilter' => $actionFilter,
            'tableFilter' => $tableFilter,
            'userFilter' => $userFilter,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'sortColumn' => $sortColumn,
            'sortOrder' => $sortOrder,
            'activeNav' => 'admin_audit',
        ]);
    }

    public function exportCsv(Request $request): Response
    {
        // Rule A4: CSV export is owner only, itself audited
        Auth::requireRole('owner');

        $pdo = DB::pdo();

        $actionFilter = trim((string) $request->input('action', ''));
        $tableFilter = trim((string) $request->input('table', ''));
        $userFilter = trim((string) $request->input('user_id', ''));
        $dateFrom = trim((string) $request->input('date_from', ''));
        $dateTo = trim((string) $request->input('date_to', ''));

        $where = ['1=1'];
        $params = [];

        if ($actionFilter !== '') {
            $where[] = 'a.action = :action';
            $params['action'] = $actionFilter;
        }

        if ($tableFilter !== '') {
            $where[] = 'a.table_name = :table_name';
            $params['table_name'] = $tableFilter;
        }

        if ($userFilter !== '') {
            $where[] = 'a.user_id = :user_id';
            $params['user_id'] = (int) $userFilter;
        }

        if ($dateFrom !== '') {
            $where[] = 'a.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo !== '') {
            $where[] = 'a.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT a.id, a.created_at, u.username, a.action, a.table_name, a.record_id, a.ip, a.user_agent, a.old_values, a.new_values
            FROM audit_log a
            LEFT JOIN users u ON u.id = a.user_id
            WHERE {$whereClause}
            ORDER BY a.created_at DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Audit the export action
        Audit::log('export', 'audit_log', null, null, [
            'format' => 'csv',
            'exported_records' => count($rows),
        ]);

        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['ID', 'Timestamp', 'Username', 'Action', 'Table', 'Record ID', 'IP Address', 'User Agent', 'Old Values', 'New Values']);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['id'],
                $row['created_at'],
                $row['username'] ?? 'System',
                $row['action'],
                $row['table_name'] ?? '',
                $row['record_id'] ?? '',
                $row['ip'] ?? '',
                $row['user_agent'] ?? '',
                $row['old_values'] ?? '',
                $row['new_values'] ?? '',
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        $filename = 'msantha_audit_log_' . date('Ymd_His') . '.csv';

        return new Response((string) $csvContent, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
