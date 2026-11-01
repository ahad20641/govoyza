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
