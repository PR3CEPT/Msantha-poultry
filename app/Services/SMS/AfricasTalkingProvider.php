<?php

declare(strict_types=1);

namespace App\Services\SMS;

use App\Core\Audit;

class AfricasTalkingProvider implements SmsProviderInterface
{
    private string $username;
    private string $apiKey;
    private ?string $senderId;
    private string $endpoint;

    public function __construct(string $username, string $apiKey, ?string $senderId = null, bool $sandbox = false)
    {
        $this->username = $username;
        $this->apiKey = $apiKey;
        $this->senderId = $senderId;
        $this->endpoint = $sandbox 
            ? 'https://api.sandbox.africastalking.com/version1/messaging'
            : 'https://api.africastalking.com/version1/messaging';
    }

    public function send(string $phone, string $message): array
    {
        if (empty($this->apiKey) || empty($this->username)) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => "Africa's Talking credentials (username/apiKey) not configured.",
            ];
        }

        $postData = [
            'username' => $this->username,
            'to' => $phone,
            'message' => $message,
        ];

        if (!empty($this->senderId)) {
            $postData['from'] = $this->senderId;
        }

        $body = http_build_query($postData);

        $headers = [
            'apiKey: ' . $this->apiKey,
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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

        $json = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300 && isset($json['SMSMessageData']['Recipients'][0])) {
            $recip = $json['SMSMessageData']['Recipients'][0];
            $status = $recip['status'] ?? 'Unknown';
            if (in_array(strtolower($status), ['success', 'sent'], true)) {
                return [
                    'success' => true,
                    'message_id' => (string) ($recip['messageId'] ?? ''),
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'message_id' => (string) ($recip['messageId'] ?? null),
                'error' => "Provider rejected: " . $status,
            ];
        }

        $errMsg = $json['SMSMessageData']['Message'] ?? "HTTP error {$httpCode}";
        return [
            'success' => false,
            'message_id' => null,
            'error' => $errMsg,
        ];
    }
}

