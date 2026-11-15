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

    public function __construct(?string $username = null, ?string $apiKey = null, ?string $senderId = null, bool $sandbox = false)
    {
        $this->username = $username ?: ($_ENV['SMS_USERNAME'] ?? 'ziweto');
        $this->apiKey = $apiKey ?: ($_ENV['SMS_API_KEY'] ?? 'atsk_064dc082a3f8be360fdc8fc44c761d6b8e4ac360c5470ee49cc44b66167e36efbffe2408');
        $this->senderId = $senderId;
        $this->endpoint = $sandbox 
            ? 'https://api.sandbox.africastalking.com/version1/messaging'
            : 'https://api.africastalking.com/version1/messaging';
    }

    public function send(string $phone, string $message): array
    {
        // Mathematically enforce E.164 telecom standard format
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) > 0) {
            if (substr($phone, 0, 1) === '0') {
                $phone = '+265' . substr($phone, 1);
            } elseif (substr($phone, 0, 3) === '265') {
                $phone = '+' . $phone;
            } elseif (substr($phone, 0, 1) !== '+') {
                $phone = '+265' . $phone;
            }
        }

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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        // Fallback audit log
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        @file_put_contents(
            $logDir . '/sms_log.txt',
            date('Y-m-d H:i:s') . " | To: {$phone} | Msg: {$message} | Resp: " . ($curlErr ? $curlErr : $response) . "\n",
            FILE_APPEND
        );

        if ($response === false || !empty($curlErr)) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => "cURL error: " . $curlErr,
            ];
        }

        $json = json_decode((string)$response, true);
        if ($httpCode >= 200 && $httpCode < 300 && isset($json['SMSMessageData']['Recipients'][0])) {
            $recip = $json['SMSMessageData']['Recipients'][0];
            $status = $recip['status'] ?? 'Unknown';
            if (in_array(strtolower((string)$status), ['success', 'sent'], true)) {
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
