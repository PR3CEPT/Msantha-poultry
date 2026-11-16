<?php

declare(strict_types=1);

return [
    'roles' => [
        'owner' => [
            '*' => ['view', 'create', 'update', 'delete', 'export', 'change_roles', 'force_logout'],
        ],
        'manager' => [
            'dashboard' => ['view'],
            'flocks' => ['view', 'create', 'edit', 'update', 'delete'],
            'eggs' => ['view', 'create', 'edit', 'update', 'delete'],
            'incubation' => ['view', 'create', 'edit', 'update', 'delete'],
            'customers' => ['view', 'create', 'edit', 'update', 'delete'],
            'sales' => ['view', 'create', 'edit', 'update', 'delete'],
            'feed_drugs' => ['view', 'create', 'edit', 'update', 'delete'],
            'finance' => ['view', 'create', 'edit', 'update', 'delete'],
            'reports' => ['view'],
            'sms' => ['view', 'create', 'edit', 'update', 'delete'],
            'payments' => ['view', 'create', 'edit', 'update'],
            'admin_audit' => ['view'],
            'admin_import' => ['view', 'create'],
        ],
        'accountant' => [
            'dashboard' => ['view'],
            'finance' => ['view', 'create', 'edit', 'update', 'delete'],
            'reports' => ['view'],
            'sales' => ['view', 'create', 'edit', 'update'],
            'incubation' => ['view', 'create', 'edit', 'update'],
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
