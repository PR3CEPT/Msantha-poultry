<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\OfflineSyncService;
use Throwable;

class SyncController
{
    private OfflineSyncService $syncService;

    public function __construct(?OfflineSyncService $syncService = null)
    {
        $this->syncService = $syncService ?? new OfflineSyncService();
    }

    /**
     * Handle offline batch sync endpoint.
     * Route: POST /api/sync
     */
    public function sync(Request $request): Response
    {
        // Enforce user authentication for sync operations
        if (!Auth::check()) {
            return Response::json([
                'success' => false,
                'error' => 'Unauthenticated. Please log in before syncing offline records.',
            ], 401);
        }

        $body = $request->json();
        if (empty($body)) {
            $rawPost = $request->all();
            if (!empty($rawPost['items']) && is_string($rawPost['items'])) {
                $decoded = json_decode($rawPost['items'], true);
                if (is_array($decoded)) {
                    $body = ['items' => $decoded];
                }
            } elseif (!empty($rawPost['items']) && is_array($rawPost['items'])) {
                $body = ['items' => $rawPost['items']];
            }
        }

        $items = $body['items'] ?? [];
        if (!is_array($items) || empty($items)) {
            return Response::json([
                'success' => false,
                'error' => 'No sync items provided. Expected JSON payload with "items" array.',
            ], 422);
        }

        try {
            $userId = Auth::id();
            $result = $this->syncService->syncBatch($items, $userId);

            return Response::json($result, 200);
        } catch (Throwable $e) {
            return Response::json([
                'success' => false,
                'error' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}

