<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\NotificationService;

class NotificationController
{
    public function markSeen(Request $request): Response
    {
        $user = Auth::user();
        $userId = $user ? (int)$user['id'] : 0;
        $key = trim((string)$request->input('key', 'all'));

        NotificationService::markAsSeen($userId, $key);

        return Response::json([
            'success' => true,
            'message' => 'Notification(s) marked as seen.',
        ]);
    }

    public function dismiss(Request $request): Response
    {
        $user = Auth::user();
        $userId = $user ? (int)$user['id'] : 0;
        $key = trim((string)$request->input('key', ''));

        if ($key !== '') {
            NotificationService::dismiss($userId, $key);
        }

        return Response::json([
            'success' => true,
            'message' => 'Notification dismissed from trail.',
        ]);
    }
}

