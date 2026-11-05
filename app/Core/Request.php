<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    private string $method;
    private string $uri;
    private array $query;
    private array $post;
    private array $server;
    private array $headers;
    private array $params = [];
    private ?array $jsonBody = null;

    public function __construct(
        ?string $method = null,
        ?string $uri = null,
        ?array $query = null,
        ?array $post = null,
        ?array $server = null
    ) {
        $this->server = $server ?? $_SERVER;
        $this->method = strtoupper($method ?? ($this->server['REQUEST_METHOD'] ?? 'GET'));
        $rawUri = $uri ?? ($this->server['REQUEST_URI'] ?? '/');
        $this->uri = $this->normalizeUri($rawUri);
        $this->query = $query ?? $_GET;
        $this->post = $post ?? $_POST;
        $this->headers = $this->extractHeaders();

        if ($this->isJson()) {
            $input = file_get_contents('php://input');
            if ($input) {
                $decoded = json_decode($input, true);
                if (is_array($decoded)) {
                    $this->jsonBody = $decoded;
                }
            }
        }
    }

    public static function capture(): self
    {
        return new self();
    }

    public static function create(
        string $method,
        string $uri,
        array $params = [],
        array $headers = [],
        ?string $rawBody = null
    ): self {
        $server = [
            'REQUEST_METHOD' => strtoupper($method),
            'REQUEST_URI' => $uri,
            'REMOTE_ADDR' => '127.0.0.1',
        ];

        foreach ($headers as $key => $value) {
            $normalizedKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $server[$normalizedKey] = $value;
        }

        $query = [];
        $post = [];

        if (strtoupper($method) === 'GET') {
            $query = $params;
        } else {
            $post = $params;
        }

        $instance = new self($method, $uri, $query, $post, $server);
        if ($rawBody !== null) {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $instance->jsonBody = $decoded;
            }
        }
        return $instance;
    }

    private function normalizeUri(string $uri): string
    {
        $parsed = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = rawurldecode($parsed);

        // Strip subfolder if app is served from a subdirectory in XAMPP
        $scriptName = str_replace('\\', '/', $this->server['SCRIPT_NAME'] ?? '');
        $baseDir = dirname($scriptName);
        if ($baseDir !== '/' && $baseDir !== '\\' && $baseDir !== '.' && $baseDir !== '') {
            if (strpos($path, $baseDir) === 0) {
                $path = substr($path, strlen($baseDir));
            }
        }

        $trimmed = '/' . trim($path, '/');
        return $trimmed === '//' ? '/' : $trimmed;
    }

    private function extractHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = (string) $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $name = str_replace('_', '-', strtolower($key));
                $headers[$name] = (string) $value;
            }
        }
        return $headers;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function path(): string
    {
        return $this->uri;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function params(): array
    {
        return $this->params;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        if ($this->jsonBody === null) {
            $input = file_get_contents('php://input');
            if ($input) {
                $decoded = json_decode($input, true);
                if (is_array($decoded)) {
                    $this->jsonBody = $decoded;
                }
            }
        }

        if ($key === null) {
            return $this->jsonBody ?? [];
        }

        return $this->jsonBody[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if ($this->jsonBody !== null && array_key_exists($key, $this->jsonBody)) {
            return $this->jsonBody[$key];
        }

        if (array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }

        if (array_key_exists($key, $this->query)) {
            return $this->query[$key];
        }

        return $default;
    }

    public function all(): array
    {
        if ($this->jsonBody !== null) {
            return array_merge($this->query, $this->jsonBody);
        }
        return array_merge($this->query, $this->post);
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $lower = strtolower($name);
        return $this->headers[$lower] ?? $default;
    }

    public function isJson(): bool
    {
        $contentType = $this->header('content-type', '');
        return str_contains($contentType, 'application/json');
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

