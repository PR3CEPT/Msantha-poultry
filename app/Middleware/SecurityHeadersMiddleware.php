<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->setHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->setHeader(
            'Content-Security-Policy',
            "default-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:; connect-src 'self' https://api.paychangu.com;"
        );

        return $response;
    }
}

