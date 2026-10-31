<?php

namespace App\Support;

/**
 * Deterministic keyed hashes for looking up encrypted PII (e.g. verifying a
 * passport number on the public tracking page) without decrypting rows.
 */
final class Hashing
{
    public static function passport(string $number): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $number));

        return hash_hmac('sha256', $normalized, (string) config('app.key'));
    }
}
