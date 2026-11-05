<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\Config;
use App\Core\DB;
use App\Core\Logger;
use PDO;
use RuntimeException;
use Throwable;

class BackupService
{
    private PDO $pdo;
    private string $backupDir;

    public function __construct(?PDO $pdo = null, ?string $backupDir = null)
    {
        $this->pdo = $pdo ?? DB::pdo();
        $this->backupDir = $backupDir ?? (dirname(__DIR__, 2) . '/storage/backups');

        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Create a database backup file.
     *
     * @param int|null $userId
     * @return array<string, mixed>
     */
    public function createBackup(?int $userId = null): array
    {
        $dbName = (string) $this->pdo->query("SELECT DATABASE()")->fetchColumn();
        $timestamp = date('Ymd_His');
        $filename = "msantha_backup_{$dbName}_{$timestamp}.sql";
        $filepath = $this->backupDir . '/' . $filename;

        $handle = fopen($filepath, 'w');
        if (!$handle) {
            throw new RuntimeException("Cannot open backup file for writing: {$filepath}");
        }

        try {
            fwrite($handle, "-- Msantha Poultry Database Backup\n");
            fwrite($handle, "-- Database: {$dbName}\n");
            fwrite($handle, "-- Created At: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Server: " . php_uname() . "\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

            // Fetch all base tables
            $tablesStmt = $this->pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            $tables = $tablesStmt->fetchAll(PDO::FETCH_NUM);

            $tableCount = 0;
            $rowCount = 0;

            foreach ($tables as $tableRow) {
                $tableName = $tableRow[0];
                $tableCount++;

                // Table structure
                $createStmt = $this->pdo->query("SHOW CREATE TABLE `{$tableName}`");
                $createRow = $createStmt->fetch(PDO::FETCH_NUM);
                $createSql = $createRow[1] ?? '';

                fwrite($handle, "-- Table structure for `{$tableName}`\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                fwrite($handle, $createSql . ";\n\n");

                // Table data
                $dataStmt = $this->pdo->query("SELECT * FROM `{$tableName}`");
                $colsCount = $dataStmt->columnCount();

                $rowsBuffer = [];
                while ($row = $dataStmt->fetch(PDO::FETCH_NUM)) {
                    $rowCount++;
                    $escapedVals = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $escapedVals[] = 'NULL';
                        } else {
                            $escapedVals[] = $this->pdo->quote((string) $val);
                        }
                    }
                    $rowsBuffer[] = '(' . implode(', ', $escapedVals) . ')';

                    if (count($rowsBuffer) >= 100) {
                        fwrite($handle, "INSERT INTO `{$tableName}` VALUES\n" . implode(",\n", $rowsBuffer) . ";\n\n");
                        $rowsBuffer = [];
                    }
                }

                if (!empty($rowsBuffer)) {
                    fwrite($handle, "INSERT INTO `{$tableName}` VALUES\n" . implode(",\n", $rowsBuffer) . ";\n\n");
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
            fwrite($handle, "-- Backup Complete: {$tableCount} tables, {$rowCount} rows.\n");
            fclose($handle);

            $fileSize = filesize($filepath);

            Audit::log('create', 'backups', null, null, [
                'filename' => $filename,
                'tables' => $tableCount,
                'rows' => $rowCount,
                'size_bytes' => $fileSize,
            ], $userId);

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'tables' => $tableCount,
                'rows' => $rowCount,
                'size' => $fileSize,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        } catch (Throwable $e) {
            fclose($handle);
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
            throw $e;
        }
    }

    /**
     * Clean up backups older than the retention period (default 14 days).
     *
     * @param int $retentionDays
     * @return int Number of purged files
     */
    public function purgeOldBackups(int $retentionDays = 14): int
    {
        $purged = 0;
        $cutoff = time() - ($retentionDays * 86400);

        if (!is_dir($this->backupDir)) {
            return 0;
        }

        $files = scandir($this->backupDir) ?: [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filepath = $this->backupDir . '/' . $file;
            if (is_file($filepath) && str_ends_with($file, '.sql')) {
                $mtime = filemtime($filepath);
                if ($mtime !== false && $mtime < $cutoff) {
                    if (@unlink($filepath)) {
                        $purged++;
                    }
                }
            }
        }

        return $purged;
    }

    /**
     * List all available backups.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listBackups(): array
    {
        if (!is_dir($this->backupDir)) {
            return [];
        }

        $list = [];
        $files = scandir($this->backupDir) ?: [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || !str_ends_with($file, '.sql')) {
                continue;
            }

            $filepath = $this->backupDir . '/' . $file;
            $size = filesize($filepath) ?: 0;
            $mtime = filemtime($filepath) ?: 0;

            $list[] = [
                'filename' => $file,
                'size' => $size,
                'size_formatted' => $this->formatBytes($size),
                'created_at' => date('Y-m-d H:i:s', $mtime),
                'timestamp' => $mtime,
            ];
        }

        // Sort newest first
        usort($list, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $list;
    }

    /**
     * Format bytes into human readable string
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 2) . ' MB';
    }
}

