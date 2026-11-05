<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\DateParser;
use App\Core\DB;
use App\Core\Phone;
use PDO;
use RuntimeException;

class ImportService
{
    private PDO $pdo;
    private array $breedMap = [];
    private array $businessUnitMap = [];
    private array $customerCache = [];
    private array $feedTypeMap = [];

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? DB::pdo();
        $this->loadBreedMap();
        $this->loadBusinessUnitMap();
        $this->loadFeedTypeMap();
    }

    private function loadBreedMap(): void
    {
        $stmt = $this->pdo->query("SELECT id, code, name FROM breeds");
        while ($row = $stmt->fetch()) {
            $this->breedMap[strtolower((string) $row['code'])] = (int) $row['id'];
            $this->breedMap[strtolower((string) $row['name'])] = (int) $row['id'];
        }
    }

    private function loadBusinessUnitMap(): void
    {
        $stmt = $this->pdo->query("SELECT id, code FROM business_units");
        while ($row = $stmt->fetch()) {
            $this->businessUnitMap[strtoupper((string) $row['code'])] = (int) $row['id'];
        }
    }

    private function loadFeedTypeMap(): void
    {
        $stmt = $this->pdo->query("SELECT id, name FROM feed_types");
        while ($row = $stmt->fetch()) {
            $this->feedTypeMap[strtolower((string) $row['name'])] = (int) $row['id'];
        }
    }

    public function resolveBreedId(?string $type): ?int
    {
        if ($type === null || trim($type) === '') {
            return null;
        }

        $clean = strtolower(trim($type));

        if (str_contains($clean, 'sasso')) {
            return $this->breedMap['sasso'] ?? null;
        }
        if (str_contains($clean, 'mikolongwe')) {
            return $this->breedMap['mikolongwe'] ?? null;
        }
        if (str_contains($clean, 'local')) {
            return $this->breedMap['local'] ?? ($this->breedMap['local chicken'] ?? null);
        }
        if (str_contains($clean, 'kuroiler')) {
            return $this->breedMap['kuroiler'] ?? null;
        }
        if (str_contains($clean, 'quail')) {
            return $this->breedMap['quail'] ?? null;
        }
        if (str_contains($clean, 'turkey') || str_contains($clean, 'poult')) {
            return $this->breedMap['turkey'] ?? null;
        }
        if (str_contains($clean, 'khaki')) {
            return $this->breedMap['khaki_campbell'] ?? ($this->breedMap['khaki campbell duck'] ?? null);
        }
        if (str_contains($clean, 'pekin') || str_contains($clean, 'duck')) {
            return $this->breedMap['pekin'] ?? ($this->breedMap['pekin duck'] ?? null);
        }
        if (str_contains($clean, 'chicken') || str_contains($clean, 'chiken')) {
            return $this->breedMap['local'] ?? ($this->breedMap['mikolongwe'] ?? null);
        }

        return $this->breedMap[$clean] ?? null;
    }

    public function resolveCustomer(string $ownerRaw, ?string $contactRaw, bool $dryRun = false): int
    {
        $cleanName = trim(preg_replace('/\s+/', ' ', $ownerRaw));
        if ($cleanName === '') {
            $cleanName = 'Unknown Customer';
        }

        $phones = Phone::splitMultiple($contactRaw);
        $primaryPhone = $phones[0] ?? null;
        $altPhone = $phones[1] ?? null;

        if ($primaryPhone !== null && isset($this->customerCache['phone:' . $primaryPhone])) {
            return $this->customerCache['phone:' . $primaryPhone];
        }

        $lowerName = strtolower($cleanName);
        if (isset($this->customerCache['name:' . $lowerName])) {
            return $this->customerCache['name:' . $lowerName];
        }

        if ($primaryPhone !== null) {
            $stmt = $this->pdo->prepare("SELECT id, name, legacy_names FROM customers WHERE phone = ? OR phone_alt = ? LIMIT 1");
            $stmt->execute([$primaryPhone, $primaryPhone]);
            $existing = $stmt->fetch();
            if ($existing) {
                $this->updateCustomerLegacyNames((int) $existing['id'], $cleanName, (string) $existing['legacy_names'], $dryRun);
                $this->customerCache['phone:' . $primaryPhone] = (int) $existing['id'];
                $this->customerCache['name:' . $lowerName] = (int) $existing['id'];
                return (int) $existing['id'];
            }
        }

        $stmt = $this->pdo->prepare("SELECT id, name, phone, legacy_names FROM customers WHERE LOWER(name) = ? LIMIT 1");
        $stmt->execute([$lowerName]);
        $existing = $stmt->fetch();
        if ($existing) {
            if ($existing['phone'] === null && $primaryPhone !== null && !$dryRun) {
                $this->pdo->prepare("UPDATE customers SET phone = ?, phone_alt = ? WHERE id = ?")
                    ->execute([$primaryPhone, $altPhone, $existing['id']]);
            }
            $this->updateCustomerLegacyNames((int) $existing['id'], $cleanName, (string) $existing['legacy_names'], $dryRun);
            $this->customerCache['name:' . $lowerName] = (int) $existing['id'];
            if ($primaryPhone !== null) {
                $this->customerCache['phone:' . $primaryPhone] = (int) $existing['id'];
            }
            return (int) $existing['id'];
        }

        $loc = null;
        $displayName = $cleanName;
        if (str_contains($cleanName, ' - ')) {
            $parts = explode(' - ', $cleanName, 2);
            $displayName = trim($parts[0]);
            $loc = trim($parts[1]);
        } elseif (str_contains($cleanName, '- ')) {
            $parts = explode('- ', $cleanName, 2);
            $displayName = trim($parts[1]);
            $loc = trim($parts[0]);
        }

        $isInternal = stripos($cleanName, 'Msantha') !== false ? 1 : 0;
        $legacyNamesJson = json_encode([$cleanName], JSON_UNESCAPED_UNICODE);

        if ($dryRun) {
            $fakeId = count($this->customerCache) + 1;
            $this->customerCache['name:' . $lowerName] = $fakeId;
            if ($primaryPhone !== null) {
                $this->customerCache['phone:' . $primaryPhone] = $fakeId;
            }
            return $fakeId;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO customers (name, location, phone, phone_alt, is_internal, legacy_names, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $displayName,
            $loc,
            $primaryPhone,
            $altPhone,
            $isInternal,
            $legacyNamesJson,
        ]);
        $newId = (int) $this->pdo->lastInsertId();

        $this->customerCache['name:' . $lowerName] = $newId;
        if ($primaryPhone !== null) {
            $this->customerCache['phone:' . $primaryPhone] = $newId;
        }

        return $newId;
    }

    private function updateCustomerLegacyNames(int $customerId, string $rawName, string $currentJson, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        $names = json_decode($currentJson ?: '[]', true);
        if (!is_array($names)) {
            $names = [];
        }

        if (!in_array($rawName, $names, true)) {
            $names[] = $rawName;
            $stmt = $this->pdo->prepare("UPDATE customers SET legacy_names = ? WHERE id = ?");
            $stmt->execute([json_encode($names, JSON_UNESCAPED_UNICODE), $customerId]);
        }
    }

    // ==========================================
    // 4a. Incubation Batches (incubation_01)
    // ==========================================
    public function importIncubation01(string $filePath, bool $dryRun = false): array
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("Import file not found: {$filePath}");
        }

        $fh = fopen($filePath, 'r');
        if (!$fh) {
            throw new RuntimeException("Could not open file: {$filePath}");
        }

        $rowsInCsv = 0;
        $rowsImported = 0;
        $rowsSkipped = 0;
        $skippedDetail = [];
        $totalCost = 0;
        $totalPaid = 0;
        $totalEggsIncubated = 0;
        $batchesCreated = 0;
        $batchesUpdated = 0;
        $paymentsCreated = 0;

        fgetcsv($fh); // row 0: headers
        $initialCustomersCount = (int) $this->pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();

        $line = 1;
        while (($row = fgetcsv($fh)) !== false) {
            $line++;
            $rowsInCsv++;
            $excelRow = (int) ($row[0] ?? $line);
            $dateReceivedRaw = trim($row[1] ?? '');
            $eggType = trim($row[2] ?? '');
            $qtyEggsRaw = trim($row[3] ?? '');
            $ownerRaw = trim($row[4] ?? '');
            $contactRaw = trim($row[5] ?? '');
            $eggsIncubatedRaw = trim($row[6] ?? '');
            $dateIncubatedRaw = trim($row[7] ?? '');
            $dateTransferRaw = trim($row[8] ?? '');
            $dateHatchedRaw = trim($row[9] ?? '');
            $infertileRaw = trim($row[10] ?? '');
            $fertileRaw = trim($row[11] ?? '');
            $hatchedRaw = trim($row[12] ?? '');
            $costRaw = trim($row[14] ?? '');
            $paidRaw = trim($row[15] ?? '');
            $remarksRaw = trim($row[17] ?? '');

            $hasAnyData = false;
            foreach ($row as $idx => $val) {
                if ($idx > 0 && trim((string)$val) !== '') {
                    $hasAnyData = true;
                    break;
                }
            }

            if (!$hasAnyData) {
                $rowsSkipped++;
                $skippedDetail[] = ['row' => $excelRow, 'reason' => 'Empty row'];
                continue;
            }

            if ($excelRow === 1 || $excelRow === 2 || stripos($dateReceivedRaw, 'DATE EGGS RECEIVED') !== false || stripos($dateReceivedRaw, 'POULTRY INCUBATION') !== false) {
                $rowsSkipped++;
                $skippedDetail[] = ['row' => $excelRow, 'reason' => 'Header / Title row'];
                continue;
            }

            if (stripos($dateReceivedRaw, 'END') !== false || stripos($ownerRaw, 'END') !== false) {
                $rowsSkipped++;
                $skippedDetail[] = ['row' => $excelRow, 'reason' => 'Month-end summary row: ' . ($dateReceivedRaw ?: $ownerRaw)];
                continue;
            }

            if (stripos($dateReceivedRaw, 'YEAR') !== false || stripos($ownerRaw, 'YEAR') !== false) {
                $rowsSkipped++;
                $skippedDetail[] = ['row' => $excelRow, 'reason' => 'Year label row: ' . ($dateReceivedRaw ?: $ownerRaw)];
                continue;
            }

            if ($dateReceivedRaw === '' && $ownerRaw === '') {
                $rowsSkipped++;
                $skippedDetail[] = ['row' => $excelRow, 'reason' => 'No date or customer specified'];
                continue;
            }

            $dateReceivedParsed = DateParser::parse($dateReceivedRaw);
            $dateIncubatedParsed = DateParser::parse($dateIncubatedRaw);
            $dateTransferParsed = DateParser::parse($dateTransferRaw);
            $dateHatchedParsed = DateParser::parse($dateHatchedRaw);

            $dateReceived = $dateReceivedParsed['date'];
            $dateIncubated = $dateIncubatedParsed['date'];
            $dateTransfer = $dateTransferParsed['date'];
            $dateHatched = $dateHatchedParsed['date'];

            $eggsReceived = (int) preg_replace('/\D/', '', $qtyEggsRaw ?: '0');
            $eggsIncubated = (int) preg_replace('/\D/', '', $eggsIncubatedRaw ?: '0');
            $cost = (int) str_replace(',', '', preg_replace('/[^\d]/', '', $costRaw ?: '0'));
            $amountPaid = (int) str_replace(',', '', preg_replace('/[^\d]/', '', $paidRaw ?: '0'));

            $infertile = $infertileRaw !== '' ? (int) preg_replace('/\D/', '', $infertileRaw) : null;
            $fertile = $fertileRaw !== '' ? (int) preg_replace('/\D/', '', $fertileRaw) : null;
            $hatched = $hatchedRaw !== '' ? (int) preg_replace('/\D/', '', $hatchedRaw) : null;

            $ratePerEgg = ($eggsIncubated > 0 && $cost > 0) ? (int) round($cost / $eggsIncubated) : 0;

            if ($dateHatched !== null || $hatched !== null) {
                $status = 'completed';
            } elseif ($dateTransfer !== null) {
                $status = 'hatching';
            } elseif ($dateIncubated !== null) {
                $status = 'incubating';
            } else {
                $status = 'received';
            }

            $breedId = $this->resolveBreedId($eggType);
            $customerId = $this->resolveCustomer($ownerRaw, $contactRaw, $dryRun);

            $legacyRef = 'incubation_01:row_' . $excelRow;

            $remarks = $remarksRaw;
            $isDateSuspect = ($dateReceivedParsed['is_suspect'] || $dateIncubatedParsed['is_suspect'] || $dateHatchedParsed['is_suspect']) ? 1 : 0;
            $rawDate = $dateReceivedRaw !== '' ? $dateReceivedRaw : ($dateIncubatedRaw !== '' ? $dateIncubatedRaw : null);
            if ($isDateSuspect) {
                $suspectNote = '[Date suspect in Excel: ' . ($rawDate ?? 'unknown') . ']';
                $remarks = $remarks !== '' ? ($remarks . ' ' . $suspectNote) : $suspectNote;
            }

            if (!$dryRun) {
                $checkStmt = $this->pdo->prepare("SELECT id FROM incubation_jobs WHERE legacy_ref = ? LIMIT 1");
                $checkStmt->execute([$legacyRef]);
                $existingJob = $checkStmt->fetch();

                if ($existingJob) {
                    $jobId = (int) $existingJob['id'];
                    $updateStmt = $this->pdo->prepare("
                        UPDATE incubation_jobs SET
                            customer_id = ?, breed_id = ?, date_received = ?, eggs_received = ?,
                            eggs_incubated = ?, date_incubated = ?, date_transfer = ?, date_hatched = ?,
                            infertile = ?, fertile = ?, hatched = ?, rate_per_egg = ?, cost = ?,
                            amount_paid = ?, status = ?, remarks = ?, date_suspect = ?, raw_date = ?
                        WHERE id = ?
                    ");
                    $updateStmt->execute([
                        $customerId, $breedId, $dateReceived, $eggsReceived,
                        $eggsIncubated, $dateIncubated, $dateTransfer, $dateHatched,
                        $infertile, $fertile, $hatched, $ratePerEgg, $cost,
                        $amountPaid, $status, $remarks, $isDateSuspect, $rawDate, $jobId,
                    ]);
                    $batchesUpdated++;
                } else {
                    $insertStmt = $this->pdo->prepare("
                        INSERT INTO incubation_jobs (
                            customer_id, breed_id, date_received, eggs_received,
                            eggs_incubated, date_incubated, date_transfer, date_hatched,
                            infertile, fertile, hatched, rate_per_egg, cost,
                            amount_paid, status, remarks, date_suspect, raw_date, legacy_ref
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insertStmt->execute([
                        $customerId, $breedId, $dateReceived, $eggsReceived,
                        $eggsIncubated, $dateIncubated, $dateTransfer, $dateHatched,
                        $infertile, $fertile, $hatched, $ratePerEgg, $cost,
                        $amountPaid, $status, $remarks, $isDateSuspect, $rawDate, $legacyRef,
                    ]);
                    $jobId = (int) $this->pdo->lastInsertId();
                    $batchesCreated++;
                }

                if ($amountPaid > 0) {
                    $payCheck = $this->pdo->prepare("SELECT id FROM incubation_payments WHERE job_id = ? AND amount = ? LIMIT 1");
                    $payCheck->execute([$jobId, $amountPaid]);
                    if (!$payCheck->fetch()) {
                        $payDate = $dateReceived ?? ($dateIncubated ?? date('Y-m-d'));
                        $payInsert = $this->pdo->prepare("
                            INSERT INTO incubation_payments (job_id, paid_on, amount, method)
                            VALUES (?, ?, ?, 'cash')
                        ");
                        $payInsert->execute([$jobId, $payDate, $amountPaid]);
                        $paymentsCreated++;
                    }
                }
            } else {
                $batchesCreated++;
                if ($amountPaid > 0) {
                    $paymentsCreated++;
                }
            }

            $rowsImported++;
            $totalCost += $cost;
            $totalPaid += $amountPaid;
            $totalEggsIncubated += $eggsIncubated;
        }
        fclose($fh);

        $finalCustomersCount = (int) $this->pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        $customersCreated = $dryRun ? count($this->customerCache) : ($finalCustomersCount - $initialCustomersCount);

        $logId = null;
        if (!$dryRun) {
            $logStmt = $this->pdo->prepare("
                INSERT INTO import_log (source_file, rows_in_csv, rows_imported, rows_skipped, skipped_detail, run_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $logStmt->execute([
                basename($filePath),
                $rowsInCsv,
                $rowsImported,
                $rowsSkipped,
                json_encode($skippedDetail, JSON_UNESCAPED_UNICODE),
            ]);
            $logId = (int) $this->pdo->lastInsertId();

            Audit::log('import', 'import_log', $logId, null, [
                'file' => basename($filePath),
                'rows_in_csv' => $rowsInCsv,
                'rows_imported' => $rowsImported,
                'rows_skipped' => $rowsSkipped,
                'total_cost' => $totalCost,
                'total_paid' => $totalPaid,
            ]);
        }

        return [
            'source_file' => basename($filePath),
            'dry_run' => $dryRun,
            'rows_in_csv' => $rowsInCsv,
            'rows_imported' => $rowsImported,
            'rows_skipped' => $rowsSkipped,
            'skipped_detail' => $skippedDetail,
            'batches_created' => $batchesCreated,
            'batches_updated' => $batchesUpdated,
            'payments_created' => $paymentsCreated,
            'customers_created' => $customersCreated,
            'total_eggs_incubated' => $totalEggsIncubated,
            'total_cost' => $totalCost,
            'total_paid' => $totalPaid,
            'import_log_id' => $logId,
        ];
    }

    // ==========================================
    // 4b. Incubation Allocations (incubation_02)
    // ==========================================
    public function importIncubation02(string $filePath, bool $dryRun = false): array
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("Import file not found: {$filePath}");
        }

        $fh = fopen($filePath, 'r');
        if (!$fh) {
            throw new RuntimeException("Could not open file: {$filePath}");
        }

        $rowsInCsv = 0;
        $allocationsImported = 0;
        $activitiesImported = 0;
        $rowsSkipped = 0;
        $skippedDetail = [];
        $totalPaidAllocated = 0;
        $totalActivitiesCost = 0;

        $line = 0;
        while (($row = fgetcsv($fh)) !== false) {
            $line++;
            $rowsInCsv++;
            $excelRow = (int) ($row[0] ?? $line);

            if ($line <= 3 || $excelRow <= 2) {
                $rowsSkipped++;
                $skippedDetail[] = ['row' => $excelRow, 'reason' => 'Header row'];
                continue;
            }

            $dateRaw = trim($row[1] ?? '');
            $custRaw = trim($row[2] ?? '');
            $eggsRaw = trim($row[3] ?? '');
            $costRaw = trim($row[4] ?? '');
            $paidRaw = trim($row[6] ?? '');

            $elecRaw = trim($row[8] ?? '');
            $waterRaw = trim($row[10] ?? '');
            $labourRaw = trim($row[12] ?? '');
            $phoneRaw = trim($row[14] ?? '');
            $profitRaw = trim($row[16] ?? '');

            $actDateRaw = trim($row[19] ?? '');
            $actNameRaw = trim($row[20] ?? '');
            $actAmountRaw = trim($row[21] ?? '');

            $hasPayment = ($dateRaw !== '' && preg_match('/\d/', $dateRaw));
            $hasActivity = ($actDateRaw !== '' && preg_match('/\d/', $actDateRaw));

            if (!$hasPayment && !$hasActivity) {
                $rowsSkipped++;
                $skippedDetail[] = ['row' => $excelRow, 'reason' => 'Empty / non-data row'];
                continue;
            }

            if ($hasActivity && $actNameRaw !== '') {
                $actDate = DateParser::parse($actDateRaw)['date'] ?? '2026-04-29';
                $actAmount = (int) str_replace(',', '', preg_replace('/[^\d]/', '', $actAmountRaw ?: '0'));
                $actLegacyRef = 'incubation_02:row_' . $excelRow;

                if (!$dryRun) {
                    $actCheck = $this->pdo->prepare("SELECT id FROM supportive_activities WHERE legacy_ref = ? LIMIT 1");
                    $actCheck->execute([$actLegacyRef]);
                    if (!$actCheck->fetch()) {
                        $actStmt = $this->pdo->prepare("
                            INSERT INTO supportive_activities (activity_date, activity, amount, legacy_ref)
                            VALUES (?, ?, ?, ?)
                        ");
                        $actStmt->execute([$actDate, $actNameRaw, $actAmount, $actLegacyRef]);
                    }
                }
                $activitiesImported++;
                $totalActivitiesCost += $actAmount;
            }

            if ($hasPayment) {
                $payDate = DateParser::parse($dateRaw)['date'] ?? '2026-04-18';
                $paid = (int) str_replace(',', '', preg_replace('/[^\d]/', '', $paidRaw ?: '0'));
                $cost = (int) str_replace(',', '', preg_replace('/[^\d]/', '', $costRaw ?: '0'));
                $eggs = (int) preg_replace('/\D/', '', $eggsRaw ?: '0');

                $elec = (int) str_replace(',', '', preg_replace('/[^\d]/', '', $elecRaw ?: '0'));
                $water = (int) str_replace(',', '', preg_replace('/[^\d]/', '', $waterRaw ?: '0'));
                $labour = (int) str_replace(',', '', preg_replace('/[^\d]/', '', $labourRaw ?: '0'));
                $tel = (int) str_replace(',', '', preg_replace('/[^\d]/', '', $phoneRaw ?: '0'));
                $profit = (int) str_replace(',', '', preg_replace('/[^\d]/', '', $profitRaw ?: '0'));

                if (!$dryRun && $paid > 0) {
                    $customerId = $this->resolveCustomer($custRaw, null, false);

                    $payStmt = $this->pdo->prepare("
                        SELECT p.id
                        FROM incubation_payments p
                        JOIN incubation_jobs j ON j.id = p.job_id
                        WHERE j.customer_id = ? AND (p.paid_on = ? OR j.date_received = ?) AND p.amount = ?
                        LIMIT 1
                    ");
                    $payStmt->execute([$customerId, $payDate, $payDate, $paid]);
                    $paymentId = $payStmt->fetchColumn();

                    if (!$paymentId) {
                        $jobStmt = $this->pdo->prepare("
                            SELECT id FROM incubation_jobs
                            WHERE customer_id = ? AND (date_received = ? OR date_incubated = ?)
                            ORDER BY id DESC LIMIT 1
                        ");
                        $jobStmt->execute([$customerId, $payDate, $payDate]);
                        $jobId = $jobStmt->fetchColumn();

                        if (!$jobId) {
                            $ratePerEgg = $eggs > 0 ? (int) round($cost / $eggs) : 0;
                            $jobInsert = $this->pdo->prepare("
                                INSERT INTO incubation_jobs (customer_id, date_received, eggs_received, eggs_incubated, cost, amount_paid, rate_per_egg, status, legacy_ref)
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', ?)
                            ");
                            $jobInsert->execute([$customerId, $payDate, $eggs, $eggs, $cost, $paid, $ratePerEgg, 'incubation_02:row_' . $excelRow]);
                            $jobId = (int) $this->pdo->lastInsertId();
                        }

                        $pInsert = $this->pdo->prepare("
                            INSERT INTO incubation_payments (job_id, paid_on, amount, method)
                            VALUES (?, ?, ?, 'cash')
                        ");
                        $pInsert->execute([$jobId, $payDate, $paid]);
                        $paymentId = (int) $this->pdo->lastInsertId();
                    }

                    $allocCheck = $this->pdo->prepare("SELECT id FROM incubation_allocations WHERE payment_id = ? LIMIT 1");
                    $allocCheck->execute([(int) $paymentId]);
                    if (!$allocCheck->fetch()) {
                        $insAlloc = $this->pdo->prepare("
                            INSERT INTO incubation_allocations (payment_id, category, amount)
                            VALUES (?, ?, ?)
                        ");
                        $insAlloc->execute([(int) $paymentId, 'electricity', $elec]);
                        $insAlloc->execute([(int) $paymentId, 'water', $water]);
                        $insAlloc->execute([(int) $paymentId, 'labour', $labour]);
                        $insAlloc->execute([(int) $paymentId, 'telephone', $tel]);
                        $insAlloc->execute([(int) $paymentId, 'profit', $profit]);
                    }
                }

                $allocationsImported++;
                $totalPaidAllocated += $paid;
            }
        }
        fclose($fh);

        $logId = null;
        if (!$dryRun) {
            $logStmt = $this->pdo->prepare("
                INSERT INTO import_log (source_file, rows_in_csv, rows_imported, rows_skipped, skipped_detail, run_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $logStmt->execute([
                basename($filePath),
                $rowsInCsv,
                $allocationsImported + $activitiesImported,
                $rowsSkipped,
                json_encode($skippedDetail, JSON_UNESCAPED_UNICODE),
            ]);
            $logId = (int) $this->pdo->lastInsertId();

            Audit::log('import', 'import_log', $logId, null, [
                'file' => basename($filePath),
                'rows_in_csv' => $rowsInCsv,
                'allocations_imported' => $allocationsImported,
                'activities_imported' => $activitiesImported,
                'total_paid_allocated' => $totalPaidAllocated,
                'total_activities_cost' => $totalActivitiesCost,
            ]);
        }

        return [
            'source_file' => basename($filePath),
            'dry_run' => $dryRun,
            'rows_in_csv' => $rowsInCsv,
            'rows_imported' => $allocationsImported + $activitiesImported,
            'allocations_imported' => $allocationsImported,
            'activities_imported' => $activitiesImported,
            'rows_skipped' => $rowsSkipped,
            'skipped_detail' => $skippedDetail,
            'total_paid_allocated' => $totalPaidAllocated,
            'total_activities_cost' => $totalActivitiesCost,
            'import_log_id' => $logId,
        ];
    }

    // ==========================================
    // 4c. Flock Batches (flock_01...28)
    // ==========================================
    public function importFlock(string $filePath, bool $dryRun = false): array
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("Import file not found: {$filePath}");
        }

        $base = basename($filePath);
        // Extract sheet name / batch code from filename
        // e.g. flock_01_Poults_12th_Batch.csv -> Poults 12th Batch
        // e.g. flock_02_LOCAL_CHICKENS_1_2.csv -> LOCAL CHICKENS 1 (2)
        $cleanSheet = preg_replace('/^flock_\d+_(.+)\.csv$/i', '$1', $base);
        $cleanSheet = str_replace('_', ' ', (string) $cleanSheet);
        if (preg_match('/(\d+)\s+(\d+)$/', $cleanSheet, $m)) {
            $cleanSheet = preg_replace('/\s+\d+$/', ' (' . $m[2] . ')', $cleanSheet);
        }

        $breedId = $this->resolveBreedId($cleanSheet) ?? $this->breedMap['local'] ?? 1;

        $fh = fopen($filePath, 'r');
        if (!$fh) {
            throw new RuntimeException("Could not open file: {$filePath}");
        }

        $rowsInCsv = 0;
        $rowsImported = 0;
        $rowsSkipped = 0;
        $skippedDetail = [];
        $deathsSum = 0;
        $soldSum = 0;
        $eventsCreated = 0;

        $header0 = fgetcsv($fh); // row 0: headers
        $headerColCount = count($header0);

        // Pre-scan to find first day (start date & start count) and tray price note
        $firstDay = null;
        $trayPrice = null;
        $flockRows = [];

        $line = 1;
        while (($row = fgetcsv($fh)) !== false) {
            $line++;
            $rowsInCsv++;
            $excelRow = (int) ($row[0] ?? $line);
            $flockRows[] = ['line' => $line, 'row' => $row, 'excel_row' => $excelRow];

            // Header row in sheet body
            if ($excelRow === 1 && (trim($row[1] ?? '') === 'DATE' || trim($row[1] ?? '') === 'A: DATE')) {
                continue;
            }

            foreach ($row as $cell) {
                if (stripos((string)$cell, 'Tray of eggs') !== false) {
                    if (preg_match('/mk\s*([0-9,.]+)/i', (string)$cell, $pm)) {
                        $trayPrice = (int) str_replace([',', '.'], '', $pm[1]);
                        if (strlen((string)$trayPrice) > 6) {
                            $trayPrice = (int) substr((string)$trayPrice, 0, 5); // handle trailing .00
                        }
                    }
                }
            }

            $dateRaw = trim($row[1] ?? '');
            if ($firstDay === null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateRaw)) {
                $cnt = (int) preg_replace('/\D/', '', $row[5] ?? '0');
                if ($cnt > 0) {
                    $firstDay = [
                        'date' => $dateRaw,
                        'count' => $cnt,
                    ];
                }
            }
        }
        fclose($fh);

        $startDate = $firstDay['date'] ?? '2026-01-01';
        $startCount = $firstDay['count'] ?? 0;

        // If tray price found, update breed
        if (!$dryRun && $trayPrice !== null && $trayPrice > 0) {
            $this->pdo->prepare("UPDATE breeds SET egg_tray_price = ? WHERE id = ?")->execute([$trayPrice, $breedId]);
        }

        $flockId = 0;
        if (!$dryRun) {
            // Find or create flock
            $flockStmt = $this->pdo->prepare("SELECT id FROM flocks WHERE batch_code = ? LIMIT 1");
            $flockStmt->execute([$cleanSheet]);
            $flockId = (int) $flockStmt->fetchColumn();

            if (!$flockId) {
                $insFlock = $this->pdo->prepare("
                    INSERT INTO flocks (breed_id, batch_code, legacy_sheet, purpose, start_date, start_count, status, created_at)
                    VALUES (?, ?, ?, 'mixed', ?, ?, 'active', NOW())
                ");
                $insFlock->execute([$breedId, $cleanSheet, $cleanSheet, $startDate, $startCount]);
                $flockId = (int) $this->pdo->lastInsertId();
            } else {
                $this->pdo->prepare("UPDATE flocks SET start_date = ?, start_count = ? WHERE id = ?")
                    ->execute([$startDate, $startCount, $flockId]);
            }
        }

        // Process rows into flock_daily_legacy and flock_events
        $prevRecordDate = null;
        foreach ($flockRows as $item) {
            $excelRow = $item['excel_row'];
            $row = $item['row'];

            if ($excelRow === 1 && (trim($row[1] ?? '') === 'DATE' || trim($row[1] ?? '') === 'A: DATE')) {
                $rowsSkipped++;
                $skippedDetail[] = ['row' => $excelRow, 'reason' => 'Header row'];
                continue;
            }

            $dateRaw = trim($row[1] ?? '');
            $dayNo = trim($row[2] ?? '') !== '' ? (int) $row[2] : null;
            $weekNo = trim($row[3] ?? '') !== '' ? (int) $row[3] : null;
            $monthNo = trim($row[4] ?? '') !== '' ? (int) $row[4] : null;

            $opening = trim($row[5] ?? '') !== '' ? (int) preg_replace('/[^\d-]/', '', $row[5]) : null;
            $deaths = trim($row[6] ?? '') !== '' ? (int) preg_replace('/[^\d-]/', '', $row[6]) : null;

            // Pattern difference: check if Deaths todate is column 7
            $hasDeathsTodate = (count($row) >= 11 && stripos($header0[7] ?? '', 'todate') !== false);
            if ($hasDeathsTodate) {
                $deathsTodate = trim($row[7] ?? '') !== '' ? (int) preg_replace('/[^\d-]/', '', $row[7]) : null;
                $sold = trim($row[8] ?? '') !== '' ? (int) preg_replace('/[^\d-]/', '', $row[8]) : null;
                $balance = trim($row[9] ?? '') !== '' ? (int) preg_replace('/[^\d-]/', '', $row[9]) : null;
                $price = trim($row[10] ?? '') !== '' ? (int) preg_replace('/[^\d-]/', '', $row[10]) : null;
                $remarks = trim($row[11] ?? ($row[10] ?? ''));
            } else {
                $deathsTodate = null;
                $sold = trim($row[7] ?? '') !== '' ? (int) preg_replace('/[^\d-]/', '', $row[7]) : null;
                $balance = trim($row[8] ?? '') !== '' ? (int) preg_replace('/[^\d-]/', '', $row[8]) : null;
                $price = trim($row[9] ?? '') !== '' ? (int) preg_replace('/[^\d-]/', '', $row[9]) : null;
                $remarks = trim($row[10] ?? '');
            }

            $rowKind = (stripos($dateRaw, 'month') !== false || ($dateRaw === '' && $opening === null)) ? 'monthly_total' : 'day';

            $dateParsed = DateParser::parse($dateRaw);
            $recordDate = $dateParsed['date'];
            $isProjected = ($recordDate !== null && $recordDate > '2026-11-03') ? 1 : 0;
            $dateSuspect = $dateParsed['is_suspect'] ? 1 : 0;

            if ($recordDate !== null && $rowKind === 'day') {
                if ($prevRecordDate !== null) {
                    $prevTime = strtotime($prevRecordDate);
                    $currTime = strtotime($recordDate);
                    $diffDays = ($currTime - $prevTime) / 86400;
                    if ($diffDays <= 0 || $diffDays > 7) {
                        $dateSuspect = 1;
                    }
                }
                $prevRecordDate = $recordDate;
            }

            if (!$dryRun) {
                // Upsert flock_daily_legacy
                $insDaily = $this->pdo->prepare("
                    INSERT INTO flock_daily_legacy (
                        flock_id, excel_row, row_kind, record_date, date_suspect, is_projected,
                        day_no, week_no, month_no, opening_count, deaths, deaths_todate, sold, balance, price, remarks
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    ) ON DUPLICATE KEY UPDATE
                        row_kind = VALUES(row_kind),
                        record_date = VALUES(record_date),
                        date_suspect = VALUES(date_suspect),
                        is_projected = VALUES(is_projected),
                        day_no = VALUES(day_no),
                        week_no = VALUES(week_no),
                        month_no = VALUES(month_no),
                        opening_count = VALUES(opening_count),
                        deaths = VALUES(deaths),
                        deaths_todate = VALUES(deaths_todate),
                        sold = VALUES(sold),
                        balance = VALUES(balance),
                        price = VALUES(price),
                        remarks = VALUES(remarks)
                ");
                $insDaily->execute([
                    $flockId, $excelRow, $rowKind, $recordDate, $dateSuspect, $isProjected,
                    $dayNo, $weekNo, $monthNo, $opening, $deaths, $deathsTodate, $sold, $balance, $price, $remarks,
                ]);

                // Create authoritative events where deaths > 0 or sold > 0 or remarks
                if ($recordDate !== null && $rowKind === 'day') {
                    $legacyRef = $cleanSheet . '!row_' . $excelRow;

                    if ($deaths !== null && $deaths > 0) {
                        $checkEv = $this->pdo->prepare("SELECT id FROM flock_events WHERE flock_id = ? AND legacy_ref = ? AND event_type = 'death' LIMIT 1");
                        $checkEv->execute([$flockId, $legacyRef]);
                        if (!$checkEv->fetch()) {
                            $this->pdo->prepare("
                                INSERT INTO flock_events (flock_id, event_date, event_type, quantity, remarks, legacy_ref)
                                VALUES (?, ?, 'death', ?, ?, ?)
                            ")->execute([$flockId, $recordDate, $deaths, $remarks ?: null, $legacyRef]);
                            $eventsCreated++;
                        }
                    }

                    if ($sold !== null && $sold > 0) {
                        $checkEv = $this->pdo->prepare("SELECT id FROM flock_events WHERE flock_id = ? AND legacy_ref = ? AND event_type = 'sale' LIMIT 1");
                        $checkEv->execute([$flockId, $legacyRef]);
                        if (!$checkEv->fetch()) {
                            $amount = ($price !== null && $price > 0) ? ($sold * $price) : null;
                            $this->pdo->prepare("
                                INSERT INTO flock_events (flock_id, event_date, event_type, quantity, unit_price, amount, remarks, legacy_ref)
                                VALUES (?, ?, 'sale', ?, ?, ?, ?, ?)
                            ")->execute([$flockId, $recordDate, $sold, $price, $amount, $remarks ?: null, $legacyRef]);
                            $eventsCreated++;
                        }
                    }

                    if ($remarks !== '' && !is_numeric($remarks) && ($deaths === null || $deaths === 0) && ($sold === null || $sold === 0)) {
                        $checkEv = $this->pdo->prepare("SELECT id FROM flock_events WHERE flock_id = ? AND legacy_ref = ? AND event_type = 'note' LIMIT 1");
                        $checkEv->execute([$flockId, $legacyRef]);
                        if (!$checkEv->fetch()) {
                            $this->pdo->prepare("
                                INSERT INTO flock_events (flock_id, event_date, event_type, quantity, remarks, legacy_ref)
                                VALUES (?, ?, 'note', 0, ?, ?)
                            ")->execute([$flockId, $recordDate, $remarks, $legacyRef]);
                            $eventsCreated++;
                        }
                    }
                }
            }

            $rowsImported++;
            if ($deaths !== null && $deaths > 0) $deathsSum += $deaths;
            if ($sold !== null && $sold > 0) $soldSum += $sold;
        }

        $logId = null;
        if (!$dryRun) {
            $logStmt = $this->pdo->prepare("
                INSERT INTO import_log (source_file, rows_in_csv, rows_imported, rows_skipped, skipped_detail, run_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $logStmt->execute([
                $base,
                $rowsInCsv,
                $rowsImported,
                $rowsSkipped,
                json_encode($skippedDetail, JSON_UNESCAPED_UNICODE),
            ]);
            $logId = (int) $this->pdo->lastInsertId();

            Audit::log('import', 'import_log', $logId, null, [
                'file' => $base,
                'batch_code' => $cleanSheet,
                'rows_in_csv' => $rowsInCsv,
                'rows_imported' => $rowsImported,
                'events_created' => $eventsCreated,
            ]);
        }

        return [
            'source_file' => $base,
            'dry_run' => $dryRun,
            'batch_code' => $cleanSheet,
            'rows_in_csv' => $rowsInCsv,
            'rows_imported' => $rowsImported,
            'rows_skipped' => $rowsSkipped,
            'skipped_detail' => $skippedDetail,
            'start_date' => $startDate,
            'start_count' => $startCount,
            'total_deaths' => $deathsSum,
            'total_sold' => $soldSum,
            'events_created' => $eventsCreated,
            'import_log_id' => $logId,
        ];
    }

    // ==========================================
    // 4d. Ledgers (ledger_2022...2026)
    // ==========================================
    public function importLedger(string $filePath, bool $dryRun = false): array
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("Import file not found: {$filePath}");
        }

        $base = basename($filePath);
        preg_match('/(\d{4})/', $base, $ym);
        $year = (int) ($ym[1] ?? 2022);
        $sourceSheet = "POULTRY TRANSACTIONS {$year}";

        $fh = fopen($filePath, 'r');
        if (!$fh) {
            throw new RuntimeException("Could not open file: {$filePath}");
        }

        $header = fgetcsv($fh);
        $rowsInCsv = 0;
        $rowsImported = 0;
        $rowsSkipped = 0;
        $skippedDetail = [];

        $currentMonth = 'January';
        $currentMonthNum = 1;
        $validMonths = [
            'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
            'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
            'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
        ];

        $line = 1;
        while (($row = fgetcsv($fh)) !== false) {
            $line++;
            $rowsInCsv++;
            $excelRow = (int) ($row[0] ?? $line);

            // Carry forward month
            $m = trim($row[1] ?? '');
            if ($m !== '') {
                $cleanM = strtolower($m);
                if (isset($validMonths[$cleanM])) {
                    $currentMonth = ucfirst($cleanM);
                    $currentMonthNum = $validMonths[$cleanM];
                }
            }

            $d = trim($row[2] ?? '');
            $dayNo = (is_numeric($d) && (int)$d > 0 && (int)$d <= 31) ? (int)$d : null;

            // Construct values_json mapping header names to row values
            $values = [];
            foreach ($row as $colIdx => $val) {
                $colName = $header[$colIdx] ?? (string)$colIdx;
                $values[$colName] = $val;
            }
            $valuesJson = json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            // Construct valid ledger_date if day and month are present
            $ledgerDate = null;
            if ($dayNo !== null && $currentMonthNum !== null) {
                if (checkdate($currentMonthNum, $dayNo, $year)) {
                    $ledgerDate = sprintf('%04d-%02d-%02d', $year, $currentMonthNum, $dayNo);
                }
            }

            $remarks = trim($row[count($row) - 1] ?? '');

            if (!$dryRun) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO legacy_ledger_daily (
                        source_sheet, excel_row, ledger_date, month_label, day_no, values_json, remarks
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?
                    ) ON DUPLICATE KEY UPDATE
                        ledger_date = VALUES(ledger_date),
                        month_label = VALUES(month_label),
                        day_no = VALUES(day_no),
                        values_json = VALUES(values_json),
                        remarks = VALUES(remarks)
                ");
                $stmt->execute([$sourceSheet, $excelRow, $ledgerDate, $currentMonth, $dayNo, $valuesJson, $remarks ?: null]);

                // Normalise egg production if date is valid
                if ($ledgerDate !== null) {
                    $this->normaliseLedgerRow($row, $header, $ledgerDate, $sourceSheet . '!row_' . $excelRow);
                }
            }

            $rowsImported++;
        }
        fclose($fh);

        $logId = null;
        if (!$dryRun) {
            $logStmt = $this->pdo->prepare("
                INSERT INTO import_log (source_file, rows_in_csv, rows_imported, rows_skipped, skipped_detail, run_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $logStmt->execute([
                $base,
                $rowsInCsv,
                $rowsImported,
                $rowsSkipped,
                json_encode($skippedDetail, JSON_UNESCAPED_UNICODE),
            ]);
            $logId = (int) $this->pdo->lastInsertId();

            Audit::log('import', 'import_log', $logId, null, [
                'file' => $base,
                'source_sheet' => $sourceSheet,
                'rows_in_csv' => $rowsInCsv,
                'rows_imported' => $rowsImported,
            ]);
        }

        return [
            'source_file' => $base,
            'dry_run' => $dryRun,
            'source_sheet' => $sourceSheet,
            'rows_in_csv' => $rowsInCsv,
            'rows_imported' => $rowsImported,
            'rows_skipped' => $rowsSkipped,
            'skipped_detail' => $skippedDetail,
            'import_log_id' => $logId,
        ];
    }

    private function normaliseLedgerRow(array $row, array $header, string $date, string $legacyRef): void
    {
        // 1. Egg production per breed
        $breedCols = [
            'MIKOLONGWE' => ['daily' => 'EGG PRODUCTION | MIKOLONGWE | Daily', 'incubated' => 'EGG PRODUCTION | MIKOLONGWE | TOTAL EGGS INCUBATED'],
            'KUROILERS'  => ['daily' => 'EGG PRODUCTION | KUROILERS | DAILY',  'incubated' => 'EGG PRODUCTION | KUROILERS | TOTAL EGGS INCUBATED'],
            'TURKEYS'    => ['daily' => 'EGG PRODUCTION | TURKEYS | DAILY',    'incubated' => 'EGG PRODUCTION | TURKEYS | TOTAL EGGS INCUBATED'],
            'DUCKS'      => ['daily' => 'EGG PRODUCTION | DUCKS | Daily',      'incubated' => 'EGG PRODUCTION | DUCKS | TOTAL EGGS INCUBATED'],
        ];

        foreach ($breedCols as $breedKey => $cols) {
            $dailyColIdx = array_search($cols['daily'], $header, true);
            $incColIdx = array_search($cols['incubated'], $header, true);

            $collected = ($dailyColIdx !== false && isset($row[$dailyColIdx])) ? (int) preg_replace('/\D/', '', $row[$dailyColIdx]) : 0;
            $incubated = ($incColIdx !== false && isset($row[$incColIdx])) ? (int) preg_replace('/\D/', '', $row[$incColIdx]) : 0;

            if ($collected > 0 || $incubated > 0) {
                $breedId = $this->resolveBreedId($breedKey) ?? 1;
                $chk = $this->pdo->prepare("SELECT id FROM egg_production_daily WHERE breed_id = ? AND prod_date = ? LIMIT 1");
                $chk->execute([$breedId, $date]);
                if (!$chk->fetch()) {
                    $this->pdo->prepare("
                        INSERT INTO egg_production_daily (breed_id, prod_date, collected, incubated, legacy_ref)
                        VALUES (?, ?, ?, ?, ?)
                    ")->execute([$breedId, $date, $collected, $incubated, $legacyRef]);
                }
            }
        }

        // 2. Feed purchases
        $feedCols = [
            'Layers mash' => ['kg' => 'FEEDS PURCHASED in kgs | Layers mash', 'cost' => 'AA: VALUE OF FEED PURCHASED, DRUGS | Layers Mash'],
            'Pullet grower' => ['kg' => 'FEEDS PURCHASED in kgs | Pullet grower', 'cost' => 'AB: VALUE OF FEED PURCHASED, DRUGS | Pullet grower'],
            'Pullet starter' => ['kg' => 'FEEDS PURCHASED in kgs | Pullet starter', 'cost' => 'AC: VALUE OF FEED PURCHASED, DRUGS | Pullet starter'],
        ];

        foreach ($feedCols as $feedName => $cNames) {
            $kgIdx = false;
            $costIdx = false;
            foreach ($header as $hIdx => $hName) {
                if (stripos($hName, $feedName) !== false && stripos($hName, 'kg') !== false) $kgIdx = $hIdx;
                if (stripos($hName, $feedName) !== false && (stripos($hName, 'VALUE') !== false || stripos($hName, 'cost') !== false)) $costIdx = $hIdx;
            }

            $kg = ($kgIdx !== false && isset($row[$kgIdx])) ? (float) preg_replace('/[^\d.]/', '', $row[$kgIdx]) : 0.0;
            $cost = ($costIdx !== false && isset($row[$costIdx])) ? (int) preg_replace('/\D/', '', $row[$costIdx]) : 0;

            if ($kg > 0 || $cost > 0) {
                $feedTypeId = $this->feedTypeMap[strtolower($feedName)] ?? 1;
                $fchk = $this->pdo->prepare("SELECT id FROM feed_purchases WHERE purchase_date = ? AND feed_type_id = ? AND cost = ? LIMIT 1");
                $fchk->execute([$date, $feedTypeId, $cost]);
                if (!$fchk->fetch()) {
                    $this->pdo->prepare("
                        INSERT INTO feed_purchases (purchase_date, feed_type_id, quantity_kg, cost, legacy_ref)
                        VALUES (?, ?, ?, ?, ?)
                    ")->execute([$date, $feedTypeId, $kg, $cost, $legacyRef]);
                }
            }
        }

        // 3. Drug purchases
        $drugCostIdx = false;
        foreach ($header as $hIdx => $hName) {
            if (stripos($hName, 'Drug costs') !== false) {
                $drugCostIdx = $hIdx;
                break;
            }
        }
        if ($drugCostIdx !== false && isset($row[$drugCostIdx])) {
            $drugCost = (int) preg_replace('/\D/', '', $row[$drugCostIdx]);
            if ($drugCost > 0) {
                $dchk = $this->pdo->prepare("SELECT id FROM drug_purchases WHERE purchase_date = ? AND cost = ? LIMIT 1");
                $dchk->execute([$date, $drugCost]);
                if (!$dchk->fetch()) {
                    $this->pdo->prepare("
                        INSERT INTO drug_purchases (purchase_date, item, cost, legacy_ref)
                        VALUES (?, 'Assorted Drugs', ?, ?)
                    ")->execute([$date, $drugCost, $legacyRef]);
                }
            }
        }

        // 4. Bank movements
        $bankedIdx = false;
        $withdrawIdx = false;
        $balanceIdx = false;
        foreach ($header as $hIdx => $hName) {
            if (stripos($hName, 'Amount banked') !== false) $bankedIdx = $hIdx;
            if (stripos($hName, 'Bank withdraws') !== false) $withdrawIdx = $hIdx;
            if (stripos($hName, 'Bank Balance') !== false) $balanceIdx = $hIdx;
        }

        $banked = ($bankedIdx !== false && isset($row[$bankedIdx])) ? (int) preg_replace('/\D/', '', $row[$bankedIdx]) : 0;
        $withdrawn = ($withdrawIdx !== false && isset($row[$withdrawIdx])) ? (int) preg_replace('/\D/', '', $row[$withdrawIdx]) : 0;
        $balance = ($balanceIdx !== false && isset($row[$balanceIdx])) ? (int) preg_replace('/\D/', '', $row[$balanceIdx]) : null;

        if ($banked > 0) {
            $bchk = $this->pdo->prepare("SELECT id FROM bank_movements WHERE move_date = ? AND kind = 'deposit' AND amount = ? LIMIT 1");
            $bchk->execute([$date, $banked]);
            if (!$bchk->fetch()) {
                $this->pdo->prepare("
                    INSERT INTO bank_movements (move_date, kind, amount, balance_after, legacy_ref)
                    VALUES (?, 'deposit', ?, ?, ?)
                ")->execute([$date, $banked, $balance, $legacyRef]);
            }
        }
        if ($withdrawn > 0) {
            $bchk = $this->pdo->prepare("SELECT id FROM bank_movements WHERE move_date = ? AND kind = 'withdrawal' AND amount = ? LIMIT 1");
            $bchk->execute([$date, $withdrawn]);
            if (!$bchk->fetch()) {
                $this->pdo->prepare("
                    INSERT INTO bank_movements (move_date, kind, amount, balance_after, legacy_ref)
                    VALUES (?, 'withdrawal', ?, ?, ?)
                ")->execute([$date, $withdrawn, $balance, $legacyRef]);
            }
        }
    }

    // ==========================================
    // 4e. Quail Egg Production
    // ==========================================
    public function importQuailProduction(string $filePath = '', bool $dryRun = false): array
    {
        if ($filePath === '' || !file_exists($filePath)) {
            $default = dirname(__DIR__, 2) . '/storage/imports/quail_egg_production_2025-26.csv';
            if (file_exists($default)) {
                $filePath = $default;
            } else {
                throw new RuntimeException("Import file not found: {$filePath}");
            }
        }

        $base = basename($filePath);
        $quailBreedId = $this->breedMap['quail'] ?? (int) ($this->pdo->query("SELECT id FROM breeds WHERE code = 'QUAIL' LIMIT 1")->fetchColumn() ?: 1);

        $fh = fopen($filePath, 'r');
        if (!$fh) {
            throw new RuntimeException("Could not open file: {$filePath}");
        }

        fgetcsv($fh); // row 0: headers
        $rowsInCsv = 0;
        $rowsImported = 0;
        $rowsSkipped = 0;
        $skippedDetail = [];
        $totalCollected = 0;

        $line = 1;
        while (($row = fgetcsv($fh)) !== false) {
            $line++;
            $rowsInCsv++;
            $excelRow = (int) ($row[0] ?? $line);

            if ($line <= 3 || $excelRow <= 2) {
                $rowsSkipped++;
                $skippedDetail[] = ['row' => $excelRow, 'reason' => 'Header row'];
                continue;
            }

            $dateRaw = trim($row[2] ?? '');
            if ($dateRaw === '' || !preg_match('/\d/', $dateRaw)) {
                $rowsSkipped++;
                $skippedDetail[] = ['row' => $excelRow, 'reason' => 'No date'];
                continue;
            }

            $dateParsed = DateParser::parse($dateRaw);
            $prodDate = $dateParsed['date'];
            $dateSuspect = $dateParsed['is_suspect'] ? 1 : 0;
            $rawDate = $dateRaw;

            // Handle Excel typo row 4 '02.12.205' -> 2025-12-02
            if ($prodDate === null && preg_match('/(\d{1,2})[.\-\/](\d{1,2})[.\-\/]205$/', $dateRaw, $m)) {
                $prodDate = sprintf('2025-%02d-%02d', (int)$m[2], (int)$m[1]);
                $dateSuspect = 1;
            }

            if ($prodDate === null) {
                $rowsSkipped++;
                $skippedDetail[] = ['row' => $excelRow, 'reason' => 'Invalid date: ' . $dateRaw];
                continue;
            }

            $birdsCount = trim($row[5] ?? '') !== '' ? (int) preg_replace('/\D/', '', $row[5]) : null;
            $collected = trim($row[6] ?? '') !== '' ? (int) preg_replace('/\D/', '', $row[6]) : 0;
            $broken = trim($row[7] ?? '') !== '' ? (int) preg_replace('/\D/', '', $row[7]) : 0;
            $consumed = trim($row[8] ?? '') !== '' ? (int) preg_replace('/\D/', '', $row[8]) : 0;
            $sold = trim($row[9] ?? '') !== '' ? (int) preg_replace('/\D/', '', $row[9]) : 0;
            $kept = trim($row[10] ?? '') !== '' ? (int) preg_replace('/\D/', '', $row[10]) : 0;
            $incubated = trim($row[12] ?? '') !== '' ? (int) preg_replace('/\D/', '', $row[12]) : 0;
            $remarks = trim($row[13] ?? '');

            $legacyRef = 'quail_egg_production:row_' . $excelRow;

            if (!$dryRun) {
                $chk = $this->pdo->prepare("SELECT id FROM egg_production_daily WHERE breed_id = ? AND prod_date = ? LIMIT 1");
                $chk->execute([$quailBreedId, $prodDate]);
                $existingId = $chk->fetchColumn();

                if ($existingId) {
                    $this->pdo->prepare("
                        UPDATE egg_production_daily SET
                            birds_count = ?, collected = ?, broken = ?, consumed = ?, sold = ?, incubated = ?, kept = ?, remarks = ?, date_suspect = ?, raw_date = ?
                        WHERE id = ?
                    ")->execute([$birdsCount, $collected, $broken, $consumed, $sold, $incubated, $kept, $remarks ?: null, $dateSuspect, $rawDate, $existingId]);
                } else {
                    $this->pdo->prepare("
                        INSERT INTO egg_production_daily (
                            breed_id, prod_date, birds_count, collected, broken, consumed, sold, incubated, kept, remarks, date_suspect, raw_date, legacy_ref
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ")->execute([$quailBreedId, $prodDate, $birdsCount, $collected, $broken, $consumed, $sold, $incubated, $kept, $remarks ?: null, $dateSuspect, $rawDate, $legacyRef]);
                }
            }

            $rowsImported++;
            $totalCollected += $collected;
        }
        fclose($fh);

        $logId = null;
        if (!$dryRun) {
            $logStmt = $this->pdo->prepare("
                INSERT INTO import_log (source_file, rows_in_csv, rows_imported, rows_skipped, skipped_detail, run_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $logStmt->execute([
                $base,
                $rowsInCsv,
                $rowsImported,
                $rowsSkipped,
                json_encode($skippedDetail, JSON_UNESCAPED_UNICODE),
            ]);
            $logId = (int) $this->pdo->lastInsertId();

            Audit::log('import', 'import_log', $logId, null, [
                'file' => $base,
                'rows_in_csv' => $rowsInCsv,
                'rows_imported' => $rowsImported,
                'total_collected' => $totalCollected,
            ]);
        }

        return [
            'source_file' => $base,
            'dry_run' => $dryRun,
            'rows_in_csv' => $rowsInCsv,
            'rows_imported' => $rowsImported,
            'rows_skipped' => $rowsSkipped,
            'skipped_detail' => $skippedDetail,
            'total_collected' => $totalCollected,
            'import_log_id' => $logId,
        ];
    }

    // ==========================================
    // 4f. Cash Books (cashbook_01...06)
    // ==========================================
    public function importCashbook(string $filePath, bool $dryRun = false): array
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("Import file not found: {$filePath}");
        }

        $base = basename($filePath);
        // Map to business unit code
        $buCode = 'POULTRY';
        if (str_contains($base, 'MZ9350')) $buCode = 'CAR_MZ9350';
        elseif (str_contains($base, 'bee')) $buCode = 'BEE';
        elseif (str_contains($base, 'NA7153')) $buCode = 'CAR_NA7153';
        elseif (str_contains($base, 'MZ8170')) $buCode = 'CAR_MZ8170';
        elseif (str_contains($base, 'MJ4324')) $buCode = 'CAR_MJ4324';

        $buId = $this->businessUnitMap[$buCode] ?? 1;

        $fh = fopen($filePath, 'r');
        if (!$fh) {
            throw new RuntimeException("Could not open file: {$filePath}");
        }

        fgetcsv($fh); // row 0: headers
        $rowsInCsv = 0;
        $rowsImported = 0;
        $rowsSkipped = 0;
        $skippedDetail = [];
        $receiptsSum = 0;
        $expenditureSum = 0;

        $line = 1;
        while (($row = fgetcsv($fh)) !== false) {
            $line++;
            $rowsInCsv++;
            $excelRow = (int) ($row[0] ?? $line);

            if ($excelRow === 1 || $excelRow === 2 || (trim($row[1] ?? '') === 'DATE' && trim($row[2] ?? '') === 'DESCRIPTION')) {
                $rowsSkipped++;
                $skippedDetail[] = ['row' => $excelRow, 'reason' => 'Header row'];
                continue;
            }

            $dateRaw = trim($row[1] ?? '');
            $desc = trim($row[2] ?? '');
            $recRaw = trim($row[3] ?? '');
            $expRaw = trim($row[4] ?? '');

            if ($dateRaw === '' && $desc === '' && $recRaw === '' && $expRaw === '') {
                $rowsSkipped++;
                $skippedDetail[] = ['row' => $excelRow, 'reason' => 'Empty row'];
                continue;
            }

            $receipt = (int) str_replace(',', '', preg_replace('/[^\d]/', '', $recRaw ?: '0'));
            $expenditure = (int) str_replace(',', '', preg_replace('/[^\d]/', '', $expRaw ?: '0'));

            // Check if month total
            $isMonthTotal = (stripos($desc, 'END') !== false || stripos($desc, 'TOTAL') !== false || stripos($dateRaw, 'END') !== false);
            $entryKind = $isMonthTotal ? 'month_total' : 'transaction';

            $dateMissing = 0;
            $dateSuspect = 0;
            $entryDate = null;
            $rawDate = $dateRaw !== '' ? $dateRaw : null;

            if ($dateRaw === '') {
                $dateMissing = 1;
            } else {
                $parsed = DateParser::parse($dateRaw);
                $entryDate = $parsed['date'];
                $dateSuspect = $parsed['is_suspect'] ? 1 : 0;
                if ($entryDate === null) {
                    $dateMissing = 1;
                }
            }

            $legacyRef = $base . ':row_' . $excelRow;

            if (!$dryRun) {
                $chk = $this->pdo->prepare("SELECT id FROM cash_entries WHERE business_unit_id = ? AND legacy_ref = ? LIMIT 1");
                $chk->execute([$buId, $legacyRef]);
                $existingId = $chk->fetchColumn();

                if ($existingId) {
                    $this->pdo->prepare("
                        UPDATE cash_entries SET
                            entry_date = ?, date_missing = ?, date_suspect = ?, raw_date = ?, description = ?, receipt = ?, expenditure = ?, entry_kind = ?
                        WHERE id = ?
                    ")->execute([$entryDate, $dateMissing, $dateSuspect, $rawDate, $desc, $receipt, $expenditure, $entryKind, $existingId]);
                } else {
                    $this->pdo->prepare("
                        INSERT INTO cash_entries (
                            business_unit_id, entry_date, date_missing, date_suspect, raw_date, description, receipt, expenditure, entry_kind, legacy_ref
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ")->execute([$buId, $entryDate, $dateMissing, $dateSuspect, $rawDate, $desc, $receipt, $expenditure, $entryKind, $legacyRef]);
                }
            }

            $rowsImported++;
            if ($entryKind === 'transaction') {
                $receiptsSum += $receipt;
                $expenditureSum += $expenditure;
            }
        }
        fclose($fh);

        $logId = null;
        if (!$dryRun) {
            $logStmt = $this->pdo->prepare("
                INSERT INTO import_log (source_file, rows_in_csv, rows_imported, rows_skipped, skipped_detail, run_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $logStmt->execute([
                $base,
                $rowsInCsv,
                $rowsImported,
                $rowsSkipped,
                json_encode($skippedDetail, JSON_UNESCAPED_UNICODE),
            ]);
            $logId = (int) $this->pdo->lastInsertId();

            Audit::log('import', 'import_log', $logId, null, [
                'file' => $base,
                'business_unit' => $buCode,
                'rows_in_csv' => $rowsInCsv,
                'rows_imported' => $rowsImported,
                'receipts_sum' => $receiptsSum,
                'expenditure_sum' => $expenditureSum,
            ]);
        }

        return [
            'source_file' => $base,
            'dry_run' => $dryRun,
            'business_unit' => $buCode,
            'rows_in_csv' => $rowsInCsv,
            'rows_imported' => $rowsImported,
            'rows_skipped' => $rowsSkipped,
            'skipped_detail' => $skippedDetail,
            'receipts_sum' => $receiptsSum,
            'expenditure_sum' => $expenditureSum,
            'import_log_id' => $logId,
        ];
    }

    // ==========================================
    // Multi-File Bulk Helpers
    // ==========================================
    public function importAllFlocks(bool $dryRun = false, ?string $dir = null): array
    {
        $dir = $dir ?: (dirname(__DIR__, 2) . '/storage/imports');
        $files = glob($dir . '/flock_*.csv');
        sort($files);
        $results = [];
        foreach ($files as $file) {
            $results[] = $this->importFlock($file, $dryRun);
        }
        return $results;
    }

    public function importAllLedgers(bool $dryRun = false, ?string $dir = null): array
    {
        $dir = $dir ?: (dirname(__DIR__, 2) . '/storage/imports');
        $files = glob($dir . '/ledger_*.csv');
        sort($files);
        $results = [];
        foreach ($files as $file) {
            $results[] = $this->importLedger($file, $dryRun);
        }
        return $results;
    }

    public function importAllCashbooks(bool $dryRun = false, ?string $dir = null): array
    {
        $dir = $dir ?: (dirname(__DIR__, 2) . '/storage/imports');
        $files = glob($dir . '/cashbook_*.csv');
        sort($files);
        $results = [];
        foreach ($files as $file) {
            $results[] = $this->importCashbook($file, $dryRun);
        }
        return $results;
    }

    public function importAll(bool $dryRun = false, ?string $dir = null): array
    {
        $dir = $dir ?: (dirname(__DIR__, 2) . '/storage/imports');
        return [
            'incubation_01' => $this->importIncubation01($dir . '/incubation_01_batches_2023-2026.csv', $dryRun),
            'incubation_02' => $this->importIncubation02($dir . '/incubation_02_charges_analysis_and_support_activities.csv', $dryRun),
            'flocks' => $this->importAllFlocks($dryRun, $dir),
            'ledgers' => $this->importAllLedgers($dryRun, $dir),
            'quail_production' => $this->importQuailProduction($dir . '/quail_egg_production_2025-26.csv', $dryRun),
            'cashbooks' => $this->importAllCashbooks($dryRun, $dir),
        ];
    }

    // ==========================================
    // Manifest Verification
    // ==========================================
    public function verifyManifest(?string $manifestPath = null): array
    {
        if ($manifestPath === null || $manifestPath === '') {
            $manifestPath = dirname(__DIR__, 2) . '/storage/imports/MANIFEST.csv';
        }

        if (!file_exists($manifestPath)) {
            throw new RuntimeException("Manifest not found: {$manifestPath}");
        }

        $fh = fopen($manifestPath, 'r');
        fgetcsv($fh); // header

        $importsDir = dirname($manifestPath);
        $results = [];

        while (($row = fgetcsv($fh)) !== false) {
            $fileName = trim($row[0] ?? '');
            if ($fileName === '' || !str_ends_with($fileName, '.csv') || $fileName === 'MANIFEST.csv') {
                continue;
            }

            $expectedRows = (int) ($row[3] ?? 0);
            $expectedCols = (int) ($row[4] ?? 0);
            $expectedCells = (int) ($row[5] ?? 0);

            $filePath = $importsDir . '/' . $fileName;
            if (!file_exists($filePath)) {
                $results[] = [
                    'file' => $fileName,
                    'status' => 'MISSING',
                    'expected_rows' => $expectedRows,
                    'actual_rows' => 0,
                    'expected_cells' => $expectedCells,
                    'actual_cells' => 0,
                    'pass' => false,
                ];
                continue;
            }

            // Count rows and non-empty cells from the Excel export (excluding the CSV synthetic header line 1)
            $fileFh = fopen($filePath, 'r');
            // Skip the CSV header line: "excel_row,A: ...,B: ..."
            $csvHeader = fgetcsv($fileFh);
            $actualRows = 0;
            $actualCells = 0;

            while (($fileRow = fgetcsv($fileFh)) !== false) {
                $actualRows++;
                // Skip index 0 (the synthetic 'excel_row' column) to match original Excel cell counts
                for ($i = 1; $i < count($fileRow); $i++) {
                    if (trim((string)$fileRow[$i]) !== '') {
                        $actualCells++;
                    }
                }
            }
            fclose($fileFh);

            $rowsMatch = ($actualRows === $expectedRows);
            $cellsMatch = ($actualCells === $expectedCells);

            $results[] = [
                'file' => $fileName,
                'status' => ($rowsMatch && $cellsMatch) ? 'PASS' : 'RECONCILED',
                'expected_rows' => $expectedRows,
                'actual_rows' => $actualRows,
                'expected_cells' => $expectedCells,
                'actual_cells' => $actualCells,
                'pass' => ($rowsMatch && $cellsMatch),
            ];
        }
        fclose($fh);

        return $results;
    }

    public function getImportHistory(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM import_log ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
