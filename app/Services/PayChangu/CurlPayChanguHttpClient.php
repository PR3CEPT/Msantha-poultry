<?php

declare(strict_types=1);

namespace App\Services\PayChangu;

class CurlPayChanguHttpClient implements PayChanguHttpClientInterface
{
    public function post(string $url, array $headers, array $body): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(['Content-Type: application/json', 'Accept: application/json'], $headers));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $parsed = json_decode((string) $response, true);
        return [
            'status_code' => $statusCode,
            'body' => is_array($parsed) ? $parsed : (string) $response,
        ];
    }

    public function get(string $url, array $headers): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(['Accept: application/json'], $headers));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $parsed = json_decode((string) $response, true);
        return [
            'status_code' => $statusCode,
            'body' => is_array($parsed) ? $parsed : (string) $response,
        ];
    }
}

