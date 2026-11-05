<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    private int $statusCode;
    private array $headers;
    private string $body;

    public function __construct(string $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public static function json(array $data, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return new self($json ?: '{}', $statusCode, $headers);
    }

    public static function html(string $content, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/html; charset=utf-8';
        return new self($content, $statusCode, $headers);
    }

    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return (string) $value;
            }
        }
        return null;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->body;
    }
}

