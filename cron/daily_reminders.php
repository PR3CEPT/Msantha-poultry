<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\DB;
use App\Services\FlockService;
use App\Services\SMS\SmsService;
use App\Services\VaccinationService;

require_once __DIR__ . '/../vendor/autoload.php';

Config::load(dirname(__DIR__));
date_default_timezone_set((string) Config::get('app.timezone', 'Africa/Blantyre'));

$pdo = DB::pdo();
$smsService = new SmsService($pdo);
$today = date('Y-m-d');

echo "====================================================\n";
echo " Msantha Poultry - Daily Automated SMS Reminders\n";
echo " Run Date: {$today}\n";
echo "====================================================\n";

$queuedTotal = 0;

// -------------------------------------------------------------------------
// 1. Transfer Day Reminders (move to hatching tray)
// -------------------------------------------------------------------------
$transferJobs = $pdo->query("
    SELECT j.id, j.eggs_incubated, j.expected_hatch_date, j.date_transfer,
           c.id AS customer_id, c.name, c.phone
    FROM incubation_jobs j
    JOIN customers c ON c.id = j.customer_id
    WHERE j.date_transfer = '{$today}'
      AND j.status IN ('incubating', 'received')
")->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($transferJobs) . " job(s) for hatching tray transfer today.\n";
foreach ($transferJobs as $j) {
    $outId = $smsService->queue(
        'TRANSFER_DAY',
        (string) $j['phone'],
        [
            'eggs' => $j['eggs_incubated'],
            'hatch_date' => date('d-M-Y', strtotime((string)$j['expected_hatch_date'])),
            'customer_name' => $j['name'],
        ],
        "TRANSFER_DAY:job-{$j['id']}",
        (int) $j['customer_id'],
        null,
        'incubation_jobs',
        (int) $j['id']
    );
    if ($outId !== null) {
        $queuedTotal++;
        echo " - Queued TRANSFER_DAY for Job #{$j['id']} ({$j['name']})\n";
    }
}

// -------------------------------------------------------------------------
// 2. Hatch Ready Reminders
// -------------------------------------------------------------------------
$hatchJobs = $pdo->query("
    SELECT j.id, j.eggs_incubated, j.hatched, j.cost,
           c.id AS customer_id, c.name, c.phone,
           (j.cost - COALESCE((SELECT SUM(amount) FROM incubation_payments WHERE job_id = j.id), 0)) AS balance
    FROM incubation_jobs j
    JOIN customers c ON c.id = j.customer_id
    WHERE j.expected_hatch_date <= '{$today}'
      AND j.status = 'incubating'
")->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($hatchJobs) . " job(s) reaching hatch date today.\n";
foreach ($hatchJobs as $j) {
    $incubated = max(1, (int) $j['eggs_incubated']);
    $hatched = (int) ($j['hatched'] ?? 0);
    $percent = round(($hatched / $incubated) * 100, 1);
    $balance = max(0, (int) $j['balance']);

    $outId = $smsService->queue(
        'HATCH_READY',
        (string) $j['phone'],
        [
            'eggs' => $incubated,
            'hatched' => $hatched,
            'percent' => $percent,
            'balance' => number_format($balance),
            'customer_name' => $j['name'],
        ],
        "HATCH_READY:job-{$j['id']}",
        (int) $j['customer_id'],
        null,
        'incubation_jobs',
        (int) $j['id']
    );
    if ($outId !== null) {
        $queuedTotal++;
        echo " - Queued HATCH_READY for Job #{$j['id']} ({$j['name']})\n";
    }
}

// -------------------------------------------------------------------------
// 3. Balance Reminders at 7, 14, 30 Days After Hatching
// -------------------------------------------------------------------------
$debtorJobs = $pdo->query("
    SELECT j.id, j.date_hatched, j.cost,
           c.id AS customer_id, c.name, c.phone,
           DATEDIFF('{$today}', j.date_hatched) AS days_since_hatch,
           (j.cost - COALESCE((SELECT SUM(amount) FROM incubation_payments WHERE job_id = j.id), 0)) AS balance
    FROM incubation_jobs j
    JOIN customers c ON c.id = j.customer_id
    WHERE j.status = 'completed'
      AND j.date_hatched IS NOT NULL
    HAVING balance > 0 AND days_since_hatch IN (7, 14, 30)
")->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($debtorJobs) . " completed job(s) matching 7/14/30 day balance reminder milestones.\n";
foreach ($debtorJobs as $dj) {
    $days = (int) $dj['days_since_hatch'];
    $balance = (int) $dj['balance'];
    $payLink = "https://msanthapoultry.mw/pay/" . $dj['id'];

    $outId = $smsService->queue(
        'BALANCE_REMINDER',
        (string) $dj['phone'],
        [
            'balance' => number_format($balance),
            'date' => date('d-M-Y', strtotime((string)$dj['date_hatched'])),
            'pay_link' => $payLink,
            'customer_name' => $dj['name'],
        ],
        "BALANCE_REMINDER:job-{$dj['id']}:day-{$days}",
        (int) $dj['customer_id'],
        null,
        'incubation_jobs',
        (int) $dj['id']
    );
    if ($outId !== null) {
        $queuedTotal++;
        echo " - Queued {$days}-day BALANCE_REMINDER for Job #{$dj['id']} ({$dj['name']})\n";
    }
}

// -------------------------------------------------------------------------
// 4. Vaccine Due Reminders
// -------------------------------------------------------------------------
$vaccineService = new VaccinationService();
$flockService = new FlockService($pdo);
$activeFlocks = $pdo->query("SELECT id, batch_code, breed_id, start_date FROM flocks WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);

// Send alert to management phone if configured in settings
$mgmtPhone = $pdo->query("SELECT svalue FROM settings WHERE skey = 'sms_management_alert_phone' LIMIT 1")->fetchColumn();
if ($mgmtPhone) {
    foreach ($activeFlocks as $f) {
        $schedule = $vaccineService->getSchedule((int) $f['breed_id'], (string) $f['start_date'], $today);
        foreach ($schedule as $v) {
            if ($v['status'] === 'due_today') {
                $outId = $smsService->queue(
                    'VACCINE_DUE',
                    (string) $mgmtPhone,
                    [
                        'vaccine' => $v['vaccine'],
                        'batch' => $f['batch_code'],
                    ],
                    "VACCINE_DUE:flock-{$f['id']}:vaccine-{$v['day']}:date-{$today}",
                    null,
                    null,
                    'flocks',
                    (int) $f['id']
                );
                if ($outId !== null) {
                    $queuedTotal++;
                    echo " - Queued VACCINE_DUE alert for flock {$f['batch_code']} ({$v['vaccine']})\n";
                }
            }
        }
    }
}

// -------------------------------------------------------------------------
// 5. High Mortality Alert (Day entry >= 2%)
// -------------------------------------------------------------------------
$highMortalityEntries = $pdo->query("
    SELECT e.id, e.flock_id, e.event_date, e.quantity AS deaths, f.batch_code
    FROM flock_events e
    JOIN flocks f ON f.id = e.flock_id
    WHERE e.event_date = '{$today}' AND e.event_type = 'death' AND e.quantity >= 10
")->fetchAll(PDO::FETCH_ASSOC);

if ($mgmtPhone && !empty($highMortalityEntries)) {
    foreach ($highMortalityEntries as $me) {
        $metrics = $flockService->getFlockMetrics((int) $me['flock_id']);
        $currentStock = $metrics['current_stock'];
        $deaths = (int) $me['deaths'];
        $dailyRate = $currentStock > 0 ? ($deaths / ($currentStock + $deaths)) * 100 : 0;

        if ($dailyRate >= 2.0) {
            $outId = $smsService->queue(
                'LOW_STOCK',
                (string) $mgmtPhone,
                [
                    'batch' => $me['batch_code'],
                    'balance' => $currentStock,
                    'deaths' => $deaths,
                ],
                "MORTALITY_ALERT:flock-{$me['flock_id']}:date-{$today}",
                null,
                null,
                'flocks',
                (int) $me['flock_id']
            );
            if ($outId !== null) {
                $queuedTotal++;
                echo " - Queued LOW_STOCK alert for flock {$me['batch_code']} ({$deaths} deaths, {$dailyRate}%)\n";
            }
        }
    }
}

echo "\n[DONE] Finished daily reminders. Total messages queued: {$queuedTotal}\n";

