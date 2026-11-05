<?php

declare(strict_types=1);

namespace App\Core;

class Phone
{
    /**
     * Normalizes a phone number to E.164 format (+265...)
     * Handles:
     * - 0999899620 -> +265999899620
     * - 0888123456 -> +265888123456
     * - 999899620 (Excel dropped leading zero) -> +265999899620
     * - 265999899620 -> +265999899620
     * - +265999899620 -> +265999899620
     */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        // If multiple numbers separated by slash/comma, take the first one
        if (str_contains($trimmed, '/') || str_contains($trimmed, ',') || str_contains($trimmed, ';')) {
            $parts = preg_split('#[/,;]+#', $trimmed);
            if (!empty($parts[0])) {
                $trimmed = trim($parts[0]);
            }
        }

        // Remove non-digit characters except leading plus
        $hasLeadingPlus = str_starts_with($trimmed, '+');
        $digits = preg_replace('/\D/', '', $trimmed);

        if ($digits === null || $digits === '') {
            return null;
        }

        // Case 1: Already has country code 265 (e.g. 265999899620 or +265999899620)
        if (str_starts_with($digits, '265') && strlen($digits) === 12) {
            return '+' . $digits;
        }

        // Case 2: Standard local format starting with 0 (e.g. 0999899620 or 0888123456) -> 10 digits
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+265' . substr($digits, 1);
        }

        // Case 3: Excel lost leading zero -> 9 digits starting with 9 or 8 (e.g. 999899620, 888123456)
        if (strlen($digits) === 9 && in_array($digits[0], ['9', '8'], true)) {
            return '+265' . $digits;
        }

        // Case 4: International format for non-Malawi numbers
        if ($hasLeadingPlus && strlen($digits) >= 10 && strlen($digits) <= 15) {
            return '+' . $digits;
        }

        return null;
    }

    /**
     * Splits multiple numbers in a single string (e.g. "999088749/0995222333")
     * @return string[] Array of normalized numbers
     */
    public static function splitMultiple(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $parts = preg_split('#[/,;]+#', $raw);
        $results = [];

        foreach ($parts as $part) {
            $normalized = self::normalize(trim($part));
            if ($normalized !== null && !in_array($normalized, $results, true)) {
                $results[] = $normalized;
            }
        }

        return $results;
    }

    public static function isValid(?string $raw): bool
    {
        return self::normalize($raw) !== null;
    }

    /**
     * Format for display (e.g. 0999 899 620)
     */
    public static function formatLocal(?string $phone): string
    {
        $normalized = self::normalize($phone);
        if ($normalized === null) {
            return (string) $phone;
        }

        if (str_starts_with($normalized, '+265') && strlen($normalized) === 13) {
            $local = '0' . substr($normalized, 4);
            return substr($local, 0, 4) . ' ' . substr($local, 4, 3) . ' ' . substr($local, 7);
        }

        return $normalized;
    }
}
