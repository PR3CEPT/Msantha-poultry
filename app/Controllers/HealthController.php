<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\DB;
use App\Core\Request;
use App\Core\Response;
use Throwable;

class HealthController
{
    public function check(Request $request): Response
    {
        $dbOk = false;
        $dbError = null;

        try {
            $pdo = DB::pdo();
            $stmt = $pdo->query('SELECT 1');
            $dbOk = ($stmt->fetchColumn() !== false);
        } catch (Throwable $e) {
            $dbOk = false;
            $dbError = $e->getMessage();
        }

        $payload = [
            'ok' => true,
            'db' => $dbOk,
            'timestamp' => date('Y-m-d H:i:s'),
            'timezone' => Config::get('app.timezone', 'Africa/Blantyre'),
            'app' => Config::get('app.name', 'Msantha Poultry Management System'),
            'env' => Config::get('app.env', 'production'),
        ];

        if ($dbError !== null && Config::get('app.debug', false)) {
            $payload['db_error'] = $dbError;
        }

        $statusCode = $dbOk ? 200 : 503;
        return Response::json($payload, $statusCode);
    }
}

