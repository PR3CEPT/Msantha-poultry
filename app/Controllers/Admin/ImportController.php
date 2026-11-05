<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\ImportService;
use Throwable;

class ImportController
{
    private function checkAccess(): void
    {
        Auth::requireRole(['owner', 'manager']);
    }

    public function index(Request $request): Response
    {
        $this->checkAccess();
        Audit::logView('admin/import');

        $importService = new ImportService(DB::pdo());
        $history = $importService->getImportHistory(25);

        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);

        return View::render('admin/import/index', [
            'pageTitle' => 'Data Import Engine — Msantha Poultry',
            'history' => $history,
            'flashError' => $error,
            'flashSuccess' => $success,
            'activeNav' => 'admin_import',
        ]);
    }

    public function run(Request $request): Response
    {
        $this->checkAccess();

        $module = trim((string) $request->input('module', 'eggs'));
        $isDryRun = (string) $request->input('dry_run', '1') === '1';

        // Check if a CSV file was uploaded
        if (empty($_FILES['csv_file']['tmp_name']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            $_SESSION['flash_error'] = "Please choose a valid CSV spreadsheet to upload.";
            return Response::redirect(url('/admin/import'));
        }

        $tmpFile = $_FILES['csv_file']['tmp_name'];
        $origName = $_FILES['csv_file']['name'] ?? 'uploaded.csv';

        $pdo = DB::pdo();
        $importedCount = 0;
        $skippedCount = 0;

        try {
            $handle = fopen($tmpFile, 'r');
            if ($handle === false) {
                throw new \RuntimeException("Unable to open uploaded CSV file.");
            }

            // Read header
            $headers = fgetcsv($handle);
            if ($headers === false) {
                throw new \RuntimeException("Uploaded file is empty.");
            }
            $cleanHeaders = array_map(fn($h) => strtolower(trim((string)$h)), $headers);

            if (!$isDryRun) {
                $pdo->beginTransaction();
            }

            $rowIdx = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowIdx++;
                if (empty(array_filter($row, fn($v) => trim((string)$v) !== ''))) {
                    continue; // Skip empty rows
                }

                $data = [];
                foreach ($cleanHeaders as $i => $h) {
                    $data[$h] = trim((string)($row[$i] ?? ''));
                }

                if ($module === 'eggs') {
                    $date = !empty($data['date']) ? date('Y-m-d', strtotime($data['date'])) : date('Y-m-d');
                    $collected = (int)($data['collected'] ?? 0);
                    $broken = (int)($data['broken'] ?? 0);
                    $consumed = (int)($data['consumed'] ?? 0);
                    $notes = $data['remarks'] ?? $data['notes'] ?? '';

                    if (!$isDryRun) {
                        $stmt = $pdo->prepare("INSERT INTO egg_production (prod_date, collected, broken, consumed, store_stock, notes) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$date, $collected, $broken, $consumed, max(0, $collected - $broken - $consumed), $notes]);
                    }
                    $importedCount++;

                } elseif ($module === 'flocks') {
                    $code = $data['batch_code'] ?? 'BATCH-' . date('Ymd') . '-' . $rowIdx;
                    $breedName = $data['breed_name'] ?? 'Mikolongwe';
                    $startDate = !empty($data['start_date']) ? date('Y-m-d', strtotime($data['start_date'])) : date('Y-m-d');
                    $startCount = (int)($data['start_count'] ?? 100);
                    $price = (int)($data['egg_tray_price'] ?? 6500);
                    $notes = $data['notes'] ?? '';

                    // Resolve breed
                    $bStmt = $pdo->prepare("SELECT id FROM breeds WHERE name LIKE ? OR code LIKE ? LIMIT 1");
                    $bStmt->execute(["%{$breedName}%", "%{$breedName}%"]);
                    $breedId = (int)($bStmt->fetchColumn() ?: 1);

                    if (!$isDryRun) {
                        $stmt = $pdo->prepare("INSERT INTO flocks (batch_code, breed_id, start_date, start_count, current_stock, egg_tray_price, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$code, $breedId, $startDate, $startCount, $startCount, $price, $notes]);
                    }
                    $importedCount++;

                } elseif ($module === 'events') {
                    $code = $data['batch_code'] ?? '';
                    $eventDate = !empty($data['event_date']) ? date('Y-m-d', strtotime($data['event_date'])) : date('Y-m-d');
                    $type = in_array(strtolower($data['event_type'] ?? ''), ['death', 'sale', 'cull', 'intake'], true) ? strtolower($data['event_type']) : 'death';
                    $qty = (int)($data['quantity'] ?? 1);
                    $remarks = $data['remarks'] ?? '';

                    $fStmt = $pdo->prepare("SELECT id FROM flocks WHERE batch_code = ? LIMIT 1");
                    $fStmt->execute([$code]);
                    $flockId = (int)$fStmt->fetchColumn();

                    if ($flockId > 0) {
                        if (!$isDryRun) {
                            $stmt = $pdo->prepare("INSERT INTO flock_events (flock_id, event_date, event_type, quantity, remarks) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$flockId, $eventDate, $type, $qty, $remarks]);

                            if ($type === 'death' || $type === 'sale' || $type === 'cull') {
                                $pdo->prepare("UPDATE flocks SET current_stock = GREATEST(0, current_stock - ?) WHERE id = ?")->execute([$qty, $flockId]);
                            } elseif ($type === 'intake') {
                                $pdo->prepare("UPDATE flocks SET current_stock = current_stock + ? WHERE id = ?")->execute([$qty, $flockId]);
                            }
                        }
                        $importedCount++;
                    } else {
                        $skippedCount++;
                    }

                } elseif ($module === 'incubation') {
                    $custName = $data['customer_name'] ?? 'General Customer';
                    $custPhone = $data['customer_phone'] ?? '';
                    $date = !empty($data['date_received']) ? date('Y-m-d', strtotime($data['date_received'])) : date('Y-m-d');
                    $eggsRec = (int)($data['eggs_received'] ?? 0);
                    $eggsInc = (int)($data['eggs_incubated'] ?? $eggsRec);
                    $rate = (int)($data['rate_per_egg'] ?? 150);
                    $paid = (int)($data['amount_paid'] ?? 0);

                    $isOwner = (stripos($custName, 'msantha') !== false);
                    if ($isOwner) {
                        $rate = 0;
                        $paid = 0;
                        $cost = 0;
                        $bal = 0;
                    } else {
                        $cost = $eggsInc * $rate;
                        $bal = max(0, $cost - $paid);
                    }

                    if (!$isDryRun) {
                        // Find or create customer
                        $cStmt = $pdo->prepare("SELECT id FROM customers WHERE name = ? LIMIT 1");
                        $cStmt->execute([$custName]);
                        $custId = (int)$cStmt->fetchColumn();
                        if ($custId <= 0) {
                            $pdo->prepare("INSERT INTO customers (name, phone, is_internal) VALUES (?, ?, ?)")->execute([$custName, $custPhone, $isOwner ? 1 : 0]);
                            $custId = (int)$pdo->lastInsertId();
                        }

                        $stmt = $pdo->prepare("INSERT INTO incubation_jobs (customer_id, date_received, eggs_received, eggs_incubated, rate_per_egg, cost, total_paid, balance, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'incubating')");
                        $stmt->execute([$custId, $date, $eggsRec, $eggsInc, $rate, $cost, $paid, $bal]);
                    }
                    $importedCount++;

                } elseif ($module === 'finance') {
                    $unitCode = strtoupper($data['business_unit_code'] ?? 'POULTRY');
                    $uStmt = $pdo->prepare("SELECT id FROM business_units WHERE code = ? LIMIT 1");
                    $uStmt->execute([$unitCode]);
                    $unitId = (int)($uStmt->fetchColumn() ?: 1);

                    $date = !empty($data['entry_date']) ? date('Y-m-d', strtotime($data['entry_date'])) : date('Y-m-d');
                    $desc = $data['description'] ?? 'General cash entry';
                    $rec = (int)($data['receipt'] ?? 0);
                    $exp = (int)($data['expenditure'] ?? 0);

                    if (!$isDryRun) {
                        $stmt = $pdo->prepare("INSERT INTO cash_books (business_unit_id, entry_date, entry_kind, description, receipt, expenditure) VALUES (?, ?, 'transaction', ?, ?, ?)");
                        $stmt->execute([$unitId, $date, $desc, $rec, $exp]);
                    }
                    $importedCount++;
                }
            }
            fclose($handle);

            if (!$isDryRun) {
                // Log to import_log
                $pdo->prepare("INSERT INTO import_log (source_file, rows_imported, rows_skipped) VALUES (?, ?, ?)")->execute([$origName . ' (' . strtoupper($module) . ')', $importedCount, $skippedCount]);
                $pdo->commit();
                Audit::log('import', 'import_log', null, ['file' => $origName, 'module' => $module, 'imported' => $importedCount, 'skipped' => $skippedCount]);
                $_SESSION['flash_success'] = "Successfully ingested {$importedCount} records from {$origName} into {$module}.";
            } else {
                $_SESSION['flash_success'] = "[DRY RUN VALIDATION PASSED] {$importedCount} valid rows verified from {$origName}. {$skippedCount} rows skipped. No database records modified.";
            }

        } catch (Throwable $e) {
            if (!$isDryRun && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['flash_error'] = "Ingestion error in {$origName}: " . $e->getMessage();
        }

        return Response::redirect(url('/admin/import'));
    }

    public function downloadTemplate(Request $request, string $type): Response
    {
        $this->checkAccess();

        $templates = [
            'eggs' => [
                'filename' => 'migs_eggs_template.csv',
                'headers' => ['date', 'collected', 'broken', 'consumed', 'store_stock', 'remarks'],
                'sample' => [
                    ['2026-11-01', '120', '2', '4', '114', 'Morning egg collection'],
                    ['2026-11-02', '135', '1', '6', '128', 'Peak layer output'],
                ],
            ],
            'flocks' => [
                'filename' => 'migs_flocks_template.csv',
                'headers' => ['batch_code', 'breed_name', 'start_date', 'start_count', 'egg_tray_price', 'notes'],
                'sample' => [
                    ['BATCH-2026-01', 'Mikolongwe', '2026-01-15', '500', '6500', 'Intake from Bvumbwe Research Station'],
                    ['BATCH-2026-02', 'Black Australorp', '2026-02-01', '350', '7000', 'Brooder Pen 2'],
                ],
            ],
            'events' => [
                'filename' => 'migs_flock_events_template.csv',
                'headers' => ['batch_code', 'event_date', 'event_type', 'quantity', 'remarks'],
                'sample' => [
                    ['BATCH-2026-01', '2026-02-10', 'death', '3', 'Heat stress in pen'],
                    ['BATCH-2026-01', '2026-03-01', 'sale', '50', 'Direct farmgate sale'],
                ],
            ],
            'incubation' => [
                'filename' => 'migs_incubation_template.csv',
                'headers' => ['customer_name', 'customer_phone', 'breed_name', 'date_received', 'eggs_received', 'eggs_incubated', 'rate_per_egg', 'amount_paid'],
                'sample' => [
                    ['Chikondi Phiri', '+265999123456', 'Mikolongwe', '2026-03-01', '150', '150', '150', '15000'],
                    ['Msantha Farm', '+265888899620', 'Black Australorp', '2026-03-05', '300', '300', '0', '0'],
                ],
            ],
            'finance' => [
                'filename' => 'migs_cashbook_template.csv',
                'headers' => ['business_unit_code', 'entry_date', 'description', 'receipt', 'expenditure', 'remarks'],
                'sample' => [
                    ['POULTRY', '2026-03-01', 'Egg sales 10 trays', '65000', '0', 'Cash received at farm gate'],
                    ['FEED', '2026-03-02', 'Layers mash purchase 5 bags', '0', '140000', 'Purchased from Farmers World'],
                ],
            ],
        ];

        $tpl = $templates[$type] ?? $templates['eggs'];

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $tpl['headers']);
        foreach ($tpl['sample'] as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvData = stream_get_contents($output);
        fclose($output);

        return new Response($csvData, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $tpl['filename'] . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
