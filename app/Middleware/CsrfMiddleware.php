<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

class CsrfMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        $uri = $request->uri();
        // Exempt external webhooks and API endpoints that use signature or token/session auth
        if (str_starts_with($uri, '/webhooks/') || str_starts_with($uri, '/api/')) {
            return $next($request);
        }

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            // Check header X-CSRF-TOKEN or input _csrf_token
            $token = $request->input('_csrf_token') ?? $request->header('x-csrf-token');

            if (!is_string($token) || !Csrf::validate($token)) {
                if ($request->isJson()) {
                    return Response::json(['error' => 'CSRF token mismatch', 'status' => 419], 419);
                }

                $view = View::render('errors/419', ['pageTitle' => '419 Page Expired']);
                $view->setStatusCode(419);
                return $view;
            }
        }

        return $next($request);
    }
}

