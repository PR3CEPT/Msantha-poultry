<?php

declare(strict_types=1);

namespace App\Core;

class Cipher
{
    private static function getKey(): string
    {
        $key = $_ENV['APP_KEY'] ?? 'msantha_migs_poultry_vault_secure_key_2026';
        return hash('sha256', (string) $key, true);
    }

    /**
     * Encrypt a plain text string into a base64-encoded encrypted payload.
     */
    public static function encrypt(string $plain): string
    {
        if ($plain === '') {
            return '';
        }
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt($plain, 'AES-256-CBC', self::getKey(), OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $ciphertext);
    }

    /**
     * Decrypt an encrypted payload back into the original plain text.
     */
    public static function decrypt(?string $payload): ?string
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 17) {
            // Not a valid base64 or too short; might be plain text
            return $payload;
        }

        $iv = substr($raw, 0, 16);
        $ciphertext = substr($raw, 16);
        $decrypted = openssl_decrypt($ciphertext, 'AES-256-CBC', self::getKey(), OPENSSL_RAW_DATA, $iv);

        return $decrypted !== false ? $decrypted : $payload;
    }
}
