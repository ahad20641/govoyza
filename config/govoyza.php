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
    | Currency
    |--------------------------------------------------------------------------
    */

    'currency' => env('GOVOYZA_CURRENCY', 'BDT'),

];
