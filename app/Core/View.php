<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class View
{
    private static string $viewsPath = '';

    public static function setViewsPath(string $path): void
    {
        self::$viewsPath = rtrim($path, '/\\');
    }

    public static function getViewsPath(): string
    {
        if (self::$viewsPath === '') {
            self::$viewsPath = dirname(__DIR__) . '/Views';
        }
        return self::$viewsPath;
    }

    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): Response
    {
        $content = self::renderPartial($view, $data);

        if ($layout === null) {
            return Response::html($content);
        }

        $layoutFile = self::resolvePath($layout);
        if (!file_exists($layoutFile)) {
            throw new RuntimeException("Layout file not found: {$layoutFile}");
        }

        extract(array_merge($data, ['content' => $content]));

        ob_start();
        try {
            include $layoutFile;
            $html = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return Response::html($html);
    }

    public static function renderPartial(string $view, array $data = []): string
    {
        $viewFile = self::resolvePath($view);
        if (!file_exists($viewFile)) {
            throw new RuntimeException("View file not found: {$viewFile}");
        }

        extract($data);

        ob_start();
        try {
            include $viewFile;
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    private static function resolvePath(string $view): string
    {
        $clean = str_replace('.', '/', $view);
        return self::getViewsPath() . '/' . ltrim($clean, '/') . '.php';
    }
}

