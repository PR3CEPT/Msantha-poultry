<?php

declare(strict_types=1);

namespace App\Services\SMS;

class GenericHttpProvider implements SmsProviderInterface
{
    private string $url;
    private string $method;
    private array $headers;
    private string $bodyTemplate;
    private ?string $senderId;

    /**
     * @param array<int, string> $headers
     */
    public function __construct(
        string $url,
        string $method = 'POST',
        array $headers = [],
        string $bodyTemplate = '{"to":"{phone}","message":"{message}","from":"{sender_id}"}',
        ?string $senderId = null
    ) {
        $this->url = $url;
        $this->method = strtoupper($method);
        $this->headers = $headers;
        $this->bodyTemplate = $bodyTemplate;
        $this->senderId = $senderId;
    }

    public function send(string $phone, string $message): array
    {
        if (empty($this->url)) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => "Generic HTTP SMS gateway URL is not configured.",
            ];
        }

        $replacedBody = str_replace(
            ['{phone}', '{message}', '{sender_id}'],
            [addslashes($phone), addslashes($message), addslashes((string)$this->senderId)],
            $this->bodyTemplate
        );

        $ch = curl_init();
        if ($this->method === 'POST') {
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $replacedBody);
        } else {
            $queryUrl = $this->url . (str_contains($this->url, '?') ? '&' : '?') . http_build_query([
                'to' => $phone,
                'message' => $message,
                'from' => $this->senderId ?? '',
            ]);
            curl_setopt($ch, CURLOPT_URL, $queryUrl);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($curlErr)) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => "cURL error: " . $curlErr,
            ];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $json = json_decode((string) $response, true);
            $msgId = null;
            if (is_array($json)) {
                $msgId = (string) ($json['id'] ?? $json['message_id'] ?? $json['msg_id'] ?? ('http_' . time()));
            } else {
                $msgId = 'http_' . time();
            }

            return [
                'success' => true,
                'message_id' => $msgId,
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'message_id' => null,
            'error' => "Gateway returned HTTP code {$httpCode}: " . substr((string) $response, 0, 200),
        ];
    }
}

