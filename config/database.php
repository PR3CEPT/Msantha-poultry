<?php

declare(strict_types=1);

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// Automatically enable SSL for TiDB Cloud Serverless or when DB_SSL is requested
$isSsl = !empty($_ENV['DB_SSL']) || (isset($_ENV['DB_HOST']) && str_contains((string) $_ENV['DB_HOST'], 'tidbcloud.com'));
if ($isSsl) {
    if (!empty($_ENV['DB_SSL_CA']) && file_exists((string) $_ENV['DB_SSL_CA'])) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = (string) $_ENV['DB_SSL_CA'];
    } elseif (file_exists('/etc/ssl/certs/ca-certificates.crt')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
    } elseif (file_exists('/etc/ssl/cert.pem')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/cert.pem';
    } elseif (file_exists('C:/xampp/apache/bin/curl-ca-bundle.crt')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = 'C:/xampp/apache/bin/curl-ca-bundle.crt';
    } elseif (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
}

// Synchronize MySQL session timezone with application timezone (e.g. +02:00 for Africa/Blantyre)
$appTz = $_ENV['APP_TIMEZONE'] ?? 'Africa/Blantyre';
try {
    $tzObj = new DateTimeZone($appTz);
    $tzOffset = (new DateTime('now', $tzObj))->format('P');
    $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET time_zone = '{$tzOffset}'";
} catch (Throwable) {
    $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET time_zone = '+02:00'";
}

return [
    'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
            'database' => (($_ENV['APP_ENV'] ?? '') === 'testing')
                ? ($_ENV['DB_TEST_DATABASE'] ?? $_ENV['DB_TEST_NAME'] ?? 'msantha_poultry_test')
                : ($_ENV['DB_DATABASE'] ?? 'msantha_poultry'),
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => $options,
        ],
    ],
];

