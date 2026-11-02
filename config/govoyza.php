<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Domains
    |--------------------------------------------------------------------------
    |
    | Hostnames for each portal. Set per environment in .env so staging and
    | production can point at different subdomains without code changes.
    |
    */

    'domains' => [
        'main' => env('GOVOYZA_DOMAIN', 'govoyza.com'),
        'partner' => env('GOVOYZA_PARTNER_DOMAIN', 'partner.govoyza.com'),
        'admin' => env('GOVOYZA_ADMIN_DOMAIN', 'admin.govoyza.com'),
        'staging' => env('GOVOYZA_STAGING_DOMAIN', 'staging.govoyza.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Company defaults
    |--------------------------------------------------------------------------
    */

    'company' => [
        'name' => env('GOVOYZA_COMPANY_NAME', 'GOVOYZA'),
        'support_email' => env('GOVOYZA_SUPPORT_EMAIL', 'hello@govoyza.com'),
        'default_country' => env('GOVOYZA_DEFAULT_COUNTRY', 'BD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */

    'currency' => env('GOVOYZA_CURRENCY', 'BDT'),

    /*
    |--------------------------------------------------------------------------
    | Pricing defaults
    |--------------------------------------------------------------------------
    |
    | Zero defaults do not assert a statutory tax rate or negotiated tier rate.
    | Set approved values before quoting real orders.
    */

    'pricing' => [
        'vat_rate' => env('GOVOYZA_VAT_RATE', '0'),
        'tier_markup_reductions' => [
            'silver' => env('GOVOYZA_TIER_SILVER_MARKUP_REDUCTION', '0'),
            'gold' => env('GOVOYZA_TIER_GOLD_MARKUP_REDUCTION', '0'),
            'platinum' => env('GOVOYZA_TIER_PLATINUM_MARKUP_REDUCTION', '0'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin console
    |--------------------------------------------------------------------------
    |
    | Optional IP allowlist for admin.govoyza.com: comma-separated IPs or CIDR
    | ranges. Empty = no restriction (2FA is always required).
    |
    */

    'admin' => [
        // URL prefix of the console on its domain. Production: '' (admin.govoyza.com/).
        // Staging has no admin subdomain, so it sets GOVOYZA_ADMIN_DOMAIN=staging.govoyza.com
        // and GOVOYZA_ADMIN_PATH=admin (staging.govoyza.com/admin).
        'path' => trim((string) env('GOVOYZA_ADMIN_PATH', ''), '/'),
        'allowed_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('GOVOYZA_ADMIN_IPS', ''))))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Partner portal
    |--------------------------------------------------------------------------
    |
    | path: URL prefix on the partner domain. Production: '' (partner.govoyza.com/).
    | Staging sets GOVOYZA_PARTNER_DOMAIN=staging.govoyza.com and GOVOYZA_PARTNER_PATH=partner.
    |
    | agreement_version: raise it whenever resources/agreements/partner-agreement.md
    | changes; partners on an older version are asked to sign again.
    |
    */

    'partner' => [
        'path' => trim((string) env('GOVOYZA_PARTNER_PATH', ''), '/'),
        'agreement_version' => '2026-11-01-draft',
        'invitation_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Staging gate
    |--------------------------------------------------------------------------
    |
    | HTTP Basic Auth in front of the whole app (except /up) while a password
    | hash is set. Only staging sets it, with `php artisan govoyza:staging-password`.
    | Never set it in production.
    |
    */

    'staging_gate' => [
        'user' => env('GOVOYZA_STAGING_USER', 'govoyza'),
        'password_hash' => env('GOVOYZA_STAGING_PASSWORD_HASH'),
    ],

];
