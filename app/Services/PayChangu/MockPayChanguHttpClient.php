<?php

declare(strict_types=1);

namespace App\Services\PayChangu;

class MockPayChanguHttpClient implements PayChanguHttpClientInterface
{
    /** @var array<int, array{url: string, headers: array, body: array}> */
    public array $postRequests = [];

    /** @var array<int, array{url: string, headers: array}> */
    public array $getRequests = [];

    /** @var array{status_code: int, body: array}|null */
    public ?array $nextPostResponse = null;

    /** @var array{status_code: int, body: array}|null */
    public ?array $nextGetResponse = null;

    public function post(string $url, array $headers, array $body): array
    {
        $this->postRequests[] = [
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
        ];

        if ($this->nextPostResponse !== null) {
            $resp = $this->nextPostResponse;
            $this->nextPostResponse = null;
            return $resp;
        }

        return [
            'status_code' => 200,
            'body' => [
                'status' => 'success',
                'message' => 'Payment initiated',
                'data' => [
                    'checkout_url' => 'https://checkout.paychangu.com/pay/' . ($body['tx_ref'] ?? 'test'),
                ],
            ],
        ];
    }

    public function get(string $url, array $headers): array
    {
        $this->getRequests[] = [
            'url' => $url,
            'headers' => $headers,
        ];

        if ($this->nextGetResponse !== null) {
            $resp = $this->nextGetResponse;
            $this->nextGetResponse = null;
            return $resp;
        }

        return [
            'status_code' => 200,
            'body' => [
                'status' => 'success',
                'message' => 'Payment verified',
                'data' => [
                    'status' => 'success',
                    'currency' => 'MWK',
                    'amount' => 50000,
                    'tx_ref' => basename($url),
                    'charge_id' => 'chg_' . bin2hex(random_bytes(4)),
                    'channel' => 'airtel_money',
                ],
            ],
        ];
    }
}

