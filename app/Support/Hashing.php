<?php

namespace App\Support;

/**
 * Deterministic keyed hashes for looking up encrypted PII (e.g. verifying a
 * passport number on the public tracking page) without decrypting rows, and
 * for checking one-time codes without storing the codes.
 */
final class Hashing
{
    public static function passport(string $number): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $number));

        return hash_hmac('sha256', $normalized, (string) config('app.key'));
    }

    /** Bound to the user and purpose, so a hash copied to another row or purpose is useless. */
    public static function otp(string $code, int $userId, string $purpose): string
    {
        return hash_hmac('sha256', "{$userId}|{$purpose}|{$code}", (string) config('app.key'));
    }
}
