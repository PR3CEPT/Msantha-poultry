<?php

declare(strict_types=1);

return [
    'secret_key' => $_ENV['PAYCHANGU_SECRET_KEY'] ?? '',
    'webhook_secret' => $_ENV['PAYCHANGU_WEBHOOK_SECRET'] ?? '',
    'mode' => $_ENV['PAYCHANGU_MODE'] ?? 'test', // test or live
    'api_base' => 'https://api.paychangu.com',
    'callback_url' => $_ENV['PAYCHANGU_CALLBACK_URL'] ?? '',
    'return_url' => $_ENV['PAYCHANGU_RETURN_URL'] ?? '',
];

