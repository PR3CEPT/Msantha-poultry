<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\DB;
use App\Services\SMS\SmsService;

require_once __DIR__ . '/../vendor/autoload.php';

Config::load(dirname(__DIR__));
date_default_timezone_set((string) Config::get('app.timezone', 'Africa/Blantyre'));

$options = getopt('', ['limit:']);
$limit = isset($options['limit']) ? max(1, (int) $options['limit']) : 50;

$pdo = DB::pdo();
$smsService = new SmsService($pdo);

echo "[" . date('Y-m-d H:i:s') . "] Starting SMS queue runner (limit={$limit})...\n";

$sentCount = $smsService->sendQueued($limit);

echo "[" . date('Y-m-d H:i:s') . "] Successfully sent: {$sentCount} message(s).\n";

