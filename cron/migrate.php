<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\DB;
use Database\Seeds\DatabaseSeeder;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../database/seeds/DatabaseSeeder.php';

Config::load(dirname(__DIR__));
date_default_timezone_set((string) Config::get('app.timezone', 'Africa/Blantyre'));

$options = getopt('', [
    'fresh',
    'seed',
    'create-owner',
    'status',
    'database:',
    'confirm-db:',
    'owner-password:',
]);

$isFresh = isset($options['fresh']);
$doSeed = isset($options['seed']);
$createOwner = isset($options['create-owner']);
$showStatus = isset($options['status']);
$customDb = $options['database'] ?? null;
$confirmDb = $options['confirm-db'] ?? null;
$ownerPassword = $options['owner-password'] ?? null;

$env = (string) Config::get('app.env', 'local');
$targetDb = $customDb ?? (
    $env === 'testing'
        ? (string) Config::get('database.connections.mysql.database', 'msantha_poultry_test')
        : (string) Config::get('database.connections.mysql.database', 'msantha_poultry')
);

// If custom database is specified, update runtime config
if ($customDb !== null) {
    Config::set('database.connections.mysql.database', $customDb);
    DB::setConnection(null); // Reset singleton to connect to requested DB
}

$pdo = DB::pdo();

echo "====================================================\n";
echo " Msantha Poultry Management System - Migration Runner\n";
echo " Environment: {$env}\n";
echo " Target Database: {$targetDb}\n";
echo "====================================================\n\n";

if ($isFresh) {
    // Safety check 1: Only local or testing environments can run --fresh
    if ($env !== 'local' && $env !== 'testing') {
        echo "[SECURITY ERROR] --fresh is strictly restricted to 'local' and 'testing' environments.\n";
        echo "Current APP_ENV is: '{$env}'. Aborting.\n";
        exit(1);
    }

    // Safety check 2: Require confirmation typing the database name
    if ($confirmDb === null) {
        echo "ATTENTION: You requested --fresh. This will DROP ALL TABLES AND VIEWS in '{$targetDb}'.\n";
        echo "To confirm, please type the exact database name ('{$targetDb}'): ";
        $handle = fopen('php://stdin', 'r');
        $input = trim((string) fgets($handle));
        if ($input !== $targetDb) {
            echo "\n[ABORTED] Database confirmation mismatch. No changes made.\n";
            exit(1);
        }
        echo "Confirmation accepted.\n\n";
    } elseif ($confirmDb !== $targetDb) {
        echo "[SECURITY ERROR] Confirmation mismatch (--confirm-db='{$confirmDb}' != '{$targetDb}'). Aborting.\n";
        exit(1);
    }

    echo "[FRESH] Dropping all views and tables in {$targetDb}...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Drop views
    $stmtViews = $pdo->prepare("SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = :schema");
    $stmtViews->execute(['schema' => $targetDb]);
    $views = $stmtViews->fetchAll(PDO::FETCH_COLUMN);
    foreach ($views as $view) {
        $pdo->exec("DROP VIEW IF EXISTS `{$view}`");
        echo " - Dropped view: {$view}\n";
    }

    // Drop tables
    $stmtTables = $pdo->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = :schema");
    $stmtTables->execute(['schema' => $targetDb]);
    $tables = $stmtTables->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        echo " - Dropped table: {$table}\n";
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "[FRESH] Database cleared successfully.\n\n";
}

// Ensure migrations table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        migration VARCHAR(255) NOT NULL UNIQUE,
        batch INT UNSIGNED NOT NULL,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
");

// Get current batch number
$batchStmt = $pdo->query("SELECT MAX(batch) FROM migrations");
$currentBatch = ((int) $batchStmt->fetchColumn()) + 1;

// Get applied migrations
$appliedStmt = $pdo->query("SELECT migration FROM migrations ORDER BY id ASC");
$appliedMigrations = $appliedStmt->fetchAll(PDO::FETCH_COLUMN);

$migrationsDir = dirname(__DIR__) . '/database/migrations';
$allFiles = glob($migrationsDir . '/*.sql') ?: [];
sort($allFiles);

if ($showStatus) {
    echo "Migration Status for [{$targetDb}]:\n";
    foreach ($allFiles as $filePath) {
        $filename = basename($filePath);
        $status = in_array($filename, $appliedMigrations, true) ? '[Applied]' : '[Pending]';
        echo " {$status} {$filename}\n";
    }
    exit(0);
}

$pendingCount = 0;
foreach ($allFiles as $filePath) {
    $filename = basename($filePath);
    if (in_array($filename, $appliedMigrations, true)) {
        continue;
    }

    echo "[MIGRATE] Applying {$filename}...\n";
    $sql = file_get_contents($filePath);
    if ($sql === false || trim($sql) === '') {
        echo " - Warning: {$filename} is empty. Skipping.\n";
        continue;
    }

    try {
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $pdo->exec($sql);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        $recordStmt = $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)");
        $recordStmt->execute([
            'migration' => $filename,
            'batch' => $currentBatch,
        ]);

        echo " - Applied successfully.\n";
        $pendingCount++;
    } catch (\Throwable $e) {
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        echo "\n[ERROR] Failed to apply {$filename}:\n" . $e->getMessage() . "\n";
        exit(1);
    }
}

if ($pendingCount === 0 && !$isFresh) {
    echo "Nothing to migrate. Database [{$targetDb}] is already up to date.\n";
} else {
    echo "\n[SUCCESS] Applied {$pendingCount} migration(s) in batch {$currentBatch}.\n";
}

// Handle Seeding
if ($doSeed) {
    echo "\n----------------------------------------------------\n";
    echo " Running Baseline Seeders on [{$targetDb}]...\n";
    echo "----------------------------------------------------\n";

    DatabaseSeeder::run(false, null);
}

// Interactive Hidden Owner Creation
if ($createOwner) {
    echo "\n----------------------------------------------------\n";
    echo " Create / Update Owner Account (Interactive Prompt)\n";
    echo "----------------------------------------------------\n";

    if ($ownerPassword !== null) {
        // CLI flag provided (automated testing only)
        $pwd = $ownerPassword;
    } else {
        $pwd = readHiddenPassword("Enter new password for owner (hidden): ");
        if ($pwd === '') {
            echo "[ERROR] Password cannot be empty.\n";
            exit(1);
        }

        $confirm = readHiddenPassword("Confirm owner password (hidden): ");
        if ($pwd !== $confirm) {
            echo "[ERROR] Passwords do not match. Aborting.\n";
            exit(1);
        }
    }

    if (strlen($pwd) < 8) {
        echo "[ERROR] Password must be at least 8 characters long.\n";
        exit(1);
    }

    DatabaseSeeder::seedOwnerUser($pdo, $pwd);
    echo "[SUCCESS] Owner user account ('owner') configured successfully.\n";
}

echo "\nDone!\n";
exit(0);

/**
 * Securely reads a password without echoing characters to terminal
 */
function readHiddenPassword(string $prompt): string
{
    echo $prompt;

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $command = 'powershell -NoProfile -Command "$pwd = Read-Host -Prompt \'\' -AsSecureString; if ($pwd) { [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($pwd)) }"';
        $output = shell_exec($command);
        echo "\n";
        return trim((string) $output);
    }

    $output = shell_exec('/usr/bin/env bash -c \'read -s -p "" pass && echo $pass\'');
    echo "\n";
    return trim((string) $output);
}
