<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;

class Config
{
    private static bool $loaded = false;
    private static array $config = [];

    public static function load(string $basePath): void
    {
        if (self::$loaded) {
            return;
        }

        if (file_exists($basePath . '/.env')) {
            $dotenv = Dotenv::createImmutable($basePath);
            $dotenv->safeLoad();
        }

        $configPath = $basePath . '/config';
        if (is_dir($configPath)) {
            $files = glob($configPath . '/*.php') ?: [];
            foreach ($files as $file) {
                $key = basename($file, '.php');
                self::$config[$key] = require $file;
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load(dirname(__DIR__, 2));
        }

        if (isset(self::$config[$key])) {
            return self::$config[$key];
        }

        $segments = explode('.', $key);
        $current = self::$config;

        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } else {
                return $_ENV[$key] ?? $default;
            }
        }

        return $current;
    }

    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $current = &self::$config;

        foreach ($segments as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current = $value;
    }

    public static function reset(): void
    {
        self::$loaded = false;
        self::$config = [];
    }
}

