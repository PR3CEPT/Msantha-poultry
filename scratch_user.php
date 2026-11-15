<?php
require_once __DIR__ . '/vendor/autoload.php';
\App\Core\Config::load(__DIR__);
$pdo = \App\Core\DB::pdo();
$settings = $pdo->query("SELECT * FROM settings WHERE skey LIKE '%sms%' OR skey LIKE '%pay%' OR skey LIKE '%africa%'")->fetchAll(PDO::FETCH_ASSOC);
print_r($settings);
