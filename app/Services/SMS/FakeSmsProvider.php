<?php

declare(strict_types=1);

namespace App\Services\SMS;

class FakeSmsProvider implements SmsProviderInterface
{
    /** @var array<int, array{message_id: string, phone: string, message: string, time: string}> */
    public static array $sent = [];
    public static bool $shouldFail = false;
    public static string $failError = "Simulated SMS provider gateway failure";

    public static function reset(): void
    {
        self::$sent = [];
        self::$shouldFail = false;
        self::$failError = "Simulated SMS provider gateway failure";
    }

    public function send(string $phone, string $message): array
    {
        if (self::$shouldFail) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => self::$failError,
            ];
        }

        $msgId = 'fake_msg_' . bin2hex(random_bytes(6));
        self::$sent[] = [
            'message_id' => $msgId,
            'phone' => $phone,
            'message' => $message,
            'time' => date('Y-m-d H:i:s'),
        ];

        return [
            'success' => true,
            'message_id' => $msgId,
            'error' => null,
        ];
    }
}

