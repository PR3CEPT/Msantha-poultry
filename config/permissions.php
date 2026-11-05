<?php

declare(strict_types=1);

return [
    'roles' => [
        'owner' => [
            '*' => ['view', 'create', 'update', 'delete', 'export', 'change_roles', 'force_logout'],
        ],
        'manager' => [
            'dashboard' => ['view'],
            'flocks' => ['view', 'create', 'update', 'delete'],
            'eggs' => ['view', 'create', 'update', 'delete'],
            'incubation' => ['view', 'create', 'update', 'delete'],
            'customers' => ['view', 'create', 'update', 'delete'],
            'sales' => ['view', 'create', 'update', 'delete'],
            'feed_drugs' => ['view', 'create', 'update', 'delete'],
            'finance' => ['view', 'create', 'update', 'delete'],
            'reports' => ['view'],
            'sms' => ['view', 'create', 'update', 'delete'],
            'payments' => ['view', 'create', 'update'],
            'admin_audit' => ['view'],
            'admin_import' => ['view', 'create'],
        ],
        'accountant' => [
            'dashboard' => ['view'],
            'finance' => ['view', 'create', 'update', 'delete'],
            'reports' => ['view'],
            'sales' => ['view', 'create', 'update'],
            'incubation' => ['view', 'create'],
            'customers' => ['view'],
            'flocks' => ['view'],
            'eggs' => ['view'],
        ],
        'worker' => [
            'dashboard' => ['view'],
            'flocks' => ['view', 'create'],
            'eggs' => ['view', 'create'],
        ],
        'viewer' => [
            'dashboard' => ['view'],
            'flocks' => ['view'],
            'eggs' => ['view'],
            'incubation' => ['view'],
            'customers' => ['view'],
            'sales' => ['view'],
            'finance' => ['view'],
            'reports' => ['view'],
        ],
    ],
    // Special granular user permission rules
    'user_permissions' => [
        'isaac.chipeta' => [
            'admin_users' => ['view', 'create', 'update', 'change_roles', 'force_logout'],
        ],
    ],
];
