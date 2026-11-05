<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\DB;
use App\Services\ImportService;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

echo "\n=======================================================\n";
echo "  Msantha Poultry Management System — Data Importer\n";
echo "=======================================================\n\n";

$target = $argv[1] ?? 'help';
$dryRun = in_array('--dry-run', $argv, true);

if ($target === 'help' || $target === '--help' || $target === '-h') {
    echo "Usage: php cron/import.php <target> [--dry-run]\n\n";
    echo "Targets:\n";
    echo "  incubation_01      Import customer batches (2023-2026)\n";
    echo "  incubation_02      Import charges analysis & supportive activities\n";
    echo "  flocks             Import all 28 flock batch sheets\n";
    echo "  ledgers            Import all 5 annual transaction ledgers (2022-2026)\n";
    echo "  quail_production   Import daily quail egg production\n";
    echo "  cashbooks          Import all 6 business unit cash books\n";
    echo "  all                Import all data pack files in order (4a through 4f)\n";
    echo "  verify             Verify row counts and non-empty cells against MANIFEST.csv\n\n";
    echo "Options:\n";
    echo "  --dry-run          Parse and validate data without writing to database\n\n";
    exit(0);
}

$importService = new ImportService(DB::pdo());
$importsDir = dirname(__DIR__) . '/storage/imports';

if ($target === 'verify') {
    echo ">>> Verifying storage/imports against MANIFEST.csv ...\n\n";
    $manifestResults = $importService->verifyManifest($importsDir . '/MANIFEST.csv');
    printf("%-40s | %10s | %10s | %10s | %10s | %6s\n", "File", "Exp Rows", "Act Rows", "Exp Cells", "Act Cells", "Status");
    echo str_repeat('-', 98) . "\n";
    foreach ($manifestResults as $res) {
        printf("%-40s | %10d | %10d | %10d | %10d | %6s\n",
            substr($res['file'], 0, 40),
            $res['expected_rows'],
            $res['actual_rows'],
            $res['expected_cells'],
            $res['actual_cells'],
            $res['status']
        );
    }
    echo "\nVerification complete.\n";
    exit(0);
}

echo "Execution Mode: " . ($dryRun ? "[DRY RUN — No database writes]" : "[LIVE IMPORT]") . "\n\n";

// Execute sequentially:
$targetsToRun = [];
if ($target === 'all') {
    $targetsToRun = ['incubation_01', 'incubation_02', 'flocks', 'ledgers', 'quail_production', 'cashbooks'];
} else {
    $targetsToRun = [$target];
}

foreach ($targetsToRun as $current) {
    if ($current === 'incubation_01') {
        echo ">>> [Phase 4a] Processing Incubation Batches (incubation_01) ...\n";
        $file = $importsDir . '/incubation_01_batches_2023-2026.csv';
        $res = $importService->importIncubation01($file, $dryRun);
        echo "  Rows in CSV:      {$res['rows_in_csv']}\n";
        echo "  Rows Imported:    {$res['rows_imported']}\n";
        echo "  Rows Skipped:     {$res['rows_skipped']}\n";
        echo "  Reconciliation:   " . ($res['rows_in_csv'] === ($res['rows_imported'] + $res['rows_skipped']) ? "PASS [rows_in_csv = imported + skipped]" : "FAIL") . "\n";
        echo "  Batches Created:  {$res['batches_created']}\n";
        echo "  Batches Updated:  {$res['batches_updated']}\n";
        echo "  Payments Created: {$res['payments_created']}\n";
        echo "  Customers:        {$res['customers_created']}\n";
        echo "  Total Cost (MWK): " . number_format($res['total_cost']) . "\n";
        echo "  Total Paid (MWK): " . number_format($res['total_paid']) . "\n\n";
    } elseif ($current === 'incubation_02') {
        echo ">>> [Phase 4b] Processing Incubation Allocations (incubation_02) ...\n";
        $file = $importsDir . '/incubation_02_charges_analysis_and_support_activities.csv';
        $res = $importService->importIncubation02($file, $dryRun);
        echo "  Rows in CSV:          {$res['rows_in_csv']}\n";
        echo "  Total Items:          {$res['rows_imported']}\n";
        echo "    - Allocations:      {$res['allocations_imported']}\n";
        echo "    - Activities:       {$res['activities_imported']}\n";
        echo "  Rows Skipped:         {$res['rows_skipped']}\n";
        echo "  Total Paid Allocated: MWK " . number_format($res['total_paid_allocated']) . "\n";
        echo "  Total Activities:     MWK " . number_format($res['total_activities_cost']) . "\n\n";
    } elseif ($current === 'flocks') {
        echo ">>> [Phase 4c] Processing 28 Flock Batches ...\n";
        $flockFiles = glob($importsDir . '/flock_*.csv');
        $totalFlockRows = 0;
        $totalFlockImported = 0;
        $totalEvents = 0;
        foreach ($flockFiles as $ff) {
            $fres = $importService->importFlock($ff, $dryRun);
            $totalFlockRows += $fres['rows_in_csv'];
            $totalFlockImported += $fres['rows_imported'];
            $totalEvents += $fres['events_created'];
            echo sprintf("  %-36s : %4d rows (Deaths: %d, Sold: %d)\n",
                $fres['batch_code'], $fres['rows_imported'], $fres['total_deaths'], $fres['total_sold']);
        }
        echo "  Total Flock Daily Rows: {$totalFlockImported} (CSV total: {$totalFlockRows})\n";
        echo "  Authoritative Events:   {$totalEvents}\n\n";
    } elseif ($current === 'ledgers') {
        echo ">>> [Phase 4d] Processing 5 Annual Transaction Ledgers ...\n";
        $ledgerFiles = glob($importsDir . '/ledger_*.csv');
        $totalLedgerRows = 0;
        foreach ($ledgerFiles as $lf) {
            $lres = $importService->importLedger($lf, $dryRun);
            $totalLedgerRows += $lres['rows_imported'];
            echo "  {$lres['source_sheet']}: {$lres['rows_imported']} daily rows imported\n";
        }
        echo "  Total Legacy Ledger Rows: {$totalLedgerRows}\n\n";
    } elseif ($current === 'quail_production') {
        echo ">>> [Phase 4e] Processing Quail Egg Production ...\n";
        $qfile = $importsDir . '/quail_egg_production_2025-26.csv';
        $qres = $importService->importQuailProduction($qfile, $dryRun);
        echo "  Rows in CSV:     {$qres['rows_in_csv']}\n";
        echo "  Rows Imported:   {$qres['rows_imported']}\n";
        echo "  Rows Skipped:    {$qres['rows_skipped']}\n";
        echo "  Total Collected: {$qres['total_collected']}\n\n";
    } elseif ($current === 'cashbooks') {
        echo ">>> [Phase 4f] Processing 6 Cashbooks ...\n";
        $cashFiles = glob($importsDir . '/cashbook_*.csv');
        $totalCashRows = 0;
        $totalReceipts = 0;
        $totalExpenditures = 0;
        foreach ($cashFiles as $cf) {
            $cres = $importService->importCashbook($cf, $dryRun);
            $totalCashRows += $cres['rows_imported'];
            $totalReceipts += $cres['receipts_sum'];
            $totalExpenditures += $cres['expenditure_sum'];
            echo sprintf("  %-16s : %4d rows | Rec: MWK %12s | Exp: MWK %12s\n",
                $cres['business_unit'],
                $cres['rows_imported'],
                number_format($cres['receipts_sum']),
                number_format($cres['expenditure_sum'])
            );
        }
        echo "  Total Cash Entries:    {$totalCashRows}\n";
        echo "  Total Cash Receipts:   MWK " . number_format($totalReceipts) . "\n";
        echo "  Total Cash Expenses:   MWK " . number_format($totalExpenditures) . "\n\n";
    }
}

echo "All requested import tasks completed.\n\n";
