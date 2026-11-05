<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\BackupService;
use Throwable;

class BackupController
{
    private BackupService $backupService;

    public function __construct(?BackupService $backupService = null)
    {
        $this->backupService = $backupService ?? new BackupService();
    }

    /**
     * Backup management dashboard (owner only).
     */
    public function index(Request $request): Response
    {
        Auth::requireRole('owner');

        $backups = $this->backupService->listBackups();

        return View::render('admin/backups/index', [
            'pageTitle' => 'Database Backups',
            'activeNav' => 'admin_backups',
            'backups' => $backups,
        ]);
    }

    /**
     * Trigger manual backup.
     */
    public function create(Request $request): Response
    {
        Auth::requireRole('owner');

        try {
            $result = $this->backupService->createBackup(Auth::id());
            $_SESSION['flash_success'] = "Database backup created successfully ({$result['filename']}).";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Backup creation failed: " . $e->getMessage();
        }

        return Response::redirect(url('/admin/backups'));
    }

    /**
     * Download backup file.
     */
    public function download(Request $request, string $filename = ''): Response
    {
        Auth::requireRole('owner');

        $filename = basename($filename !== '' ? $filename : (string) $request->param('filename', ''));
        // Strictly allow only valid backup file format to prevent directory traversal
        if (!preg_match('/^msantha_backup_[a-zA-Z0-9_]+\.sql$/', $filename)) {
            $_SESSION['flash_error'] = 'Invalid backup filename requested.';
            return Response::redirect(url('/admin/backups'));
        }

        $filepath = dirname(__DIR__, 2) . '/storage/backups/' . $filename;
        if (!file_exists($filepath)) {
            $_SESSION['flash_error'] = 'Backup file not found on disk.';
            return Response::redirect(url('/admin/backups'));
        }

        Audit::log('export', 'backups', null, null, ['filename' => $filename], Auth::id());

        $content = (string) file_get_contents($filepath);
        $response = new Response($content, 200, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($content),
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);

        return $response;
    }
}

