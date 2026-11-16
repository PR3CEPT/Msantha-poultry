<?php

declare(strict_types=1);

namespace App\Services\SMS;

use App\Core\Config;
use App\Core\DB;
use PDO;

class SmsProviderFactory
{
    public static function create(?PDO $pdo = null): SmsProviderInterface
    {
        $pdo = $pdo ?? DB::pdo();

        // Fetch SMS settings from database
        $settings = [];
        try {
            $rows = $pdo->query("SELECT skey, svalue FROM settings WHERE skey LIKE 'sms_%' OR skey LIKE 'africastalking_%'")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $settings[$r['skey']] = $r['svalue'];
            }
        } catch (\Throwable $e) {
            // fallback if settings table query fails
        }

        $providerName = strtolower(trim((string) ($settings['sms_provider'] ?? Config::get('sms.provider', $_ENV['SMS_PROVIDER'] ?? 'africas_talking'))));
        $senderId = !empty($settings['sms_sender_id']) ? $settings['sms_sender_id'] : Config::get('sms.sender_id', null);

        if ($providerName === 'africas_talking') {
            $username = $settings['africastalking_username'] ?? (string) Config::get('sms.africastalking.username', $_ENV['SMS_USERNAME'] ?? 'ziweto');
            $apiKey = $settings['africastalking_api_key'] ?? (string) Config::get('sms.africastalking.api_key', $_ENV['SMS_API_KEY'] ?? '');
            $isSandbox = (bool) ($settings['africastalking_sandbox'] ?? Config::get('sms.africastalking.sandbox', false));
            return new AfricasTalkingProvider($username, $apiKey, $senderId, $isSandbox);
        }

        if ($providerName === 'generic_http') {
            $url = $settings['sms_http_url'] ?? (string) Config::get('sms.generic_http.url', '');
            $method = $settings['sms_http_method'] ?? 'POST';
            $headersRaw = $settings['sms_http_headers'] ?? '';
            $headers = array_filter(array_map('trim', explode("\n", str_replace("\r", "", $headersRaw))));
            $bodyTemplate = $settings['sms_http_body_template'] ?? '{"to":"{phone}","message":"{message}","from":"{sender_id}"}';
            return new GenericHttpProvider($url, $method, $headers, $bodyTemplate, $senderId);
        }

        return new FakeSmsProvider();
    }
}

