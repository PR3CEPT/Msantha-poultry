<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'Msantha Poultry Management System',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? '',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Africa/Blantyre',
    'locale' => $_ENV['APP_LOCALE'] ?? 'en',
    'currency' => 'MWK',
];

