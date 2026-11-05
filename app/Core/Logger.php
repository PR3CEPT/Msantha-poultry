<?php

declare(strict_types=1);

namespace App\Core;

class Logger
{
    private static ?string $logDir = null;

    private static function getLogDir(): string
    {
        if (self::$logDir === null) {
            self::$logDir = dirname(__DIR__, 2) . '/storage/logs';
            if (!is_dir(self::$logDir)) {
                @mkdir(self::$logDir, 0775, true);
            }
        }
        return self::$logDir;
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $logFile = self::getLogDir() . '/app-' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $redactedContext = self::redactSensitive($context);
        $contextJson = !empty($redactedContext) ? ' ' . json_encode($redactedContext, JSON_UNESCAPED_SLASHES) : '';
        $line = "[{$timestamp}] [" . strtoupper($level) . "] {$message}{$contextJson}" . PHP_EOL;

        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        if (Config::get('app.debug', false)) {
            self::log('debug', $message, $context);
        }
    }

    private static function redactSensitive(array $data): array
    {
        $sensitiveKeys = ['password', 'password_hash', 'secret', 'token', '_csrf_token', 'api_key', 'auth'];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::redactSensitive($value);
            } elseif (is_string($key)) {
                foreach ($sensitiveKeys as $needle) {
                    if (stripos($key, $needle) !== false) {
                        $data[$key] = '***REDACTED***';
                        break;
                    }
                }
            }
        }
        return $data;
    }
}

