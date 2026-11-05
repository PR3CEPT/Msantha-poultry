<?php

declare(strict_types=1);

namespace App\Services\SMS;

interface SmsProviderInterface
{
    /**
     * Send an SMS message to a normalized recipient phone number.
     *
     * @param string $phone E.164 phone number (e.g. +265999899620)
     * @param string $message Plain text message content
     * @return array{success: bool, message_id: ?string, error: ?string}
     */
    public function send(string $phone, string $message): array;
}

