<?php

declare(strict_types=1);

namespace App\Services\PayChangu;

interface PayChanguHttpClientInterface
{
    /**
     * @param array<int, string> $headers
     * @param array<string, mixed> $body
     * @return array{status_code: int, body: array<string, mixed>|string}
     */
    public function post(string $url, array $headers, array $body): array;

    /**
     * @param array<int, string> $headers
     * @return array{status_code: int, body: array<string, mixed>|string}
     */
    public function get(string $url, array $headers): array;
}

