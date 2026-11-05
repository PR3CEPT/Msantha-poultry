<?php

declare(strict_types=1);

namespace App\Core;

class Csrf
{
    private static function ensureSession(): void
    {
        $sessionPath = dirname(__DIR__, 2) . '/storage/cache/sessions';
        if (is_dir($sessionPath) && is_writable($sessionPath) && session_status() === PHP_SESSION_NONE) {
            @session_save_path($sessionPath);
        }

        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_start();
        }
    }

    public static function token(): string
    {
        self::ensureSession();

        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public static function validate(?string $token): bool
    {
        self::ensureSession();

        if (empty($token) || empty($_SESSION['_csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['_csrf_token'], $token);
    }

    public static function regenerate(): string
    {
        self::ensureSession();
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['_csrf_token'];
    }
}
