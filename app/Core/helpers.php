<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

use App\Core\Config;
use App\Core\Csrf;

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $baseUrl = rtrim((string) Config::get('app.url', ''), '/');
        $cleanPath = '/' . ltrim($path, '/');
        if ($baseUrl === '') {
            return $cleanPath;
        }
        return $baseUrl . ($cleanPath === '/' ? '' : $cleanPath);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $base = rtrim((string) Config::get('app.url', ''), '/');
        $cleanPath = '/' . ltrim($path, '/');
        if ($base === '') {
            return $cleanPath;
        }
        return $base . $cleanPath;
    }
}

if (!function_exists('format_mwk')) {
    function format_mwk(int|float $amount): string
    {
        $intVal = (int) round($amount);
        return 'MK ' . number_format($intVal);
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date): string
    {
        if (empty($date)) {
            return '—';
        }
        $ts = strtotime($date);
        if ($ts === false) {
            return $date;
        }
        return date('d-M-Y', $ts);
    }
}

if (!function_exists('t')) {
    function t(string $key, array $replace = []): string
    {
        static $lang = null;
        if ($lang === null) {
            $langFile = dirname(__DIR__) . '/lang/' . Config::get('app.locale', 'en') . '.php';
            if (file_exists($langFile)) {
                $lang = require $langFile;
            } else {
                $lang = [];
            }
        }

        $translation = $lang[$key] ?? $key;
        foreach ($replace as $placeholder => $value) {
            $translation = str_replace(':' . $placeholder, (string) $value, $translation);
        }

        return $translation;
    }
}

if (!function_exists('flash')) {
    function flash(string $type, string $message): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $_SESSION['flash_' . $type] = $message;
    }
}

if (!function_exists('icon')) {
    function icon(string $name, int $size = 20, string $class = ''): string
    {
        $src = asset('assets/icons/' . $name . '.svg');
        $cls = $class !== '' ? ' ' . e($class) : '';
        return '<img src="' . $src . '" width="' . $size . '" height="' . $size . '" class="svg-icon' . $cls . '" alt="' . e($name) . '" loading="lazy">';
    }
}

// Mailer & SMS Global Integrations
require_once dirname(__DIR__) . '/helpers/mailer.php';


