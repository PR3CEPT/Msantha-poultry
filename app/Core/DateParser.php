<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

class DateParser
{
    /**
     * Parses raw date string and returns structured result with quality flags.
     *
     * @param string|null $raw
     * @return array{date: ?string, is_suspect: bool, is_missing: bool, raw: string}
     */
    public static function parse(?string $raw): array
    {
        $original = (string) $raw;
        $trimmed = trim($original);

        if ($trimmed === '' || $raw === null) {
            return [
                'date' => null,
                'is_suspect' => false,
                'is_missing' => true,
                'raw' => $original,
            ];
        }

        $isSuspect = false;

        // Check for stray trailing characters like "+" (e.g. '22.05.2026+')
        if (preg_match('/[^\d.\-\/]/', $trimmed)) {
            $isSuspect = true;
            // Clean stray non-date characters from edges
            $cleaned = preg_replace('/[^\d.\-\/]/', '', $trimmed);
        } else {
            $cleaned = $trimmed;
        }

        // Check for year with 5 digits like 20204 in '24.10.20204'
        if (preg_match('/(\d{1,2})[.\-\/](\d{1,2})[.\-\/](\d{5,})/', $cleaned)) {
            return [
                'date' => null,
                'is_suspect' => true,
                'is_missing' => false,
                'raw' => $original,
            ];
        }

        // Check for missing month digit like '08.0.2024' or '16.0.2024'
        if (preg_match('/(\d{1,2})[.\-\/]0[.\-\/](\d{4})/', $cleaned)) {
            return [
                'date' => null,
                'is_suspect' => true,
                'is_missing' => false,
                'raw' => $original,
            ];
        }

        // Pattern: D.M.YYYY or DD.MM.YYYY or D/M/YYYY or DD/MM/YYYY
        if (preg_match('/^(\d{1,2})[.\-\/](\d{1,2})[.\-\/](\d{2,4})$/', $cleaned, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];

            if ($year < 100) {
                $year += ($year < 50 ? 2000 : 1900);
            }

            if ($year < 2000 || $year > 2050) {
                $isSuspect = true;
            }

            if (checkdate($month, $day, $year)) {
                $formatted = sprintf('%04d-%02d-%02d', $year, $month, $day);
                return [
                    'date' => $formatted,
                    'is_suspect' => $isSuspect,
                    'is_missing' => false,
                    'raw' => $original,
                ];
            } else {
                return [
                    'date' => null,
                    'is_suspect' => true,
                    'is_missing' => false,
                    'raw' => $original,
                ];
            }
        }

        // Pattern: YYYY-MM-DD
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $cleaned, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];

            if ($year < 2000 || $year > 2050) {
                $isSuspect = true;
            }

            if (checkdate($month, $day, $year)) {
                $formatted = sprintf('%04d-%02d-%02d', $year, $month, $day);
                return [
                    'date' => $formatted,
                    'is_suspect' => $isSuspect,
                    'is_missing' => false,
                    'raw' => $original,
                ];
            }
        }

        // Fallback with strtotime
        $timestamp = strtotime($cleaned);
        if ($timestamp !== false) {
            $year = (int) date('Y', $timestamp);
            if ($year < 2000 || $year > 2050) {
                $isSuspect = true;
            }
            return [
                'date' => date('Y-m-d', $timestamp),
                'is_suspect' => $isSuspect,
                'is_missing' => false,
                'raw' => $original,
            ];
        }

        return [
            'date' => null,
            'is_suspect' => true,
            'is_missing' => false,
            'raw' => $original,
        ];
    }

    /**
     * Parse and return only the Y-m-d string or null
     */
    public static function parseToDate(?string $raw): ?string
    {
        return self::parse($raw)['date'];
    }
}

