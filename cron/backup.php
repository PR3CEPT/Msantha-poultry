<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\DB;
use App\Core\Logger;
use App\Services\BackupService;

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap environment
Config::load(dirname(__DIR__));
date_default_timezone_set((string) Config::get('app.timezone', 'Africa/Blantyre'));

echo "====================================================\n";
echo " Msantha Poultry Management System - Nightly Backup\n";
echo " Run Time: " . date('Y-m-d H:i:s') . "\n";
echo "====================================================\n\n";

try {
    $pdo = DB::pdo();
    $backupService = new BackupService($pdo);

    echo "[1/2] Creating full database backup...\n";
    $result = $backupService->createBackup();
    echo " [OK] Backup created: {$result['filename']}\n";
    echo "      Tables: {$result['tables']} | Total rows: {$result['rows']} | Size: {$result['size']} bytes\n\n";

    echo "[2/2] Enforcing 14-day retention policy...\n";
    $purged = $backupService->purgeOldBackups(14);
    echo " [OK] Purged {$purged} backup file(s) older than 14 days.\n\n";

    Logger::info("Nightly backup completed successfully: {$result['filename']}", [
        'tables' => $result['tables'],
        'rows' => $result['rows'],
        'purged_old_files' => $purged,
    ]);

    echo "Nightly backup finished successfully.\n";
    exit(0);
} catch (Throwable $e) {
    echo "[ERROR] Backup failed: " . $e->getMessage() . "\n";
    Logger::error("Nightly backup cron failed: " . $e->getMessage(), [
        'trace' => $e->getTraceAsString(),
    ]);
    exit(1);
}

