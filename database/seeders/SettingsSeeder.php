<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/** Default, admin-editable settings. Secrets belong in .env, not here. */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['general', 'site_name', 'GOVOYZA', 'string'],
            ['general', 'support_email', null, 'string'],
            ['general', 'default_currency', 'BDT', 'string'],
            ['general', 'application_reference_prefix', 'GVZ', 'string'],
            ['branding', 'logo_path', null, 'string'],
            ['branding', 'color_primary', '#D4AF37', 'string'],
            ['branding', 'color_text', '#222222', 'string'],
            ['branding', 'color_text_secondary', '#666666', 'string'],
            ['branding', 'color_surface', '#F8F8F8', 'string'],
            ['uploads', 'default_max_size_kb', '5120', 'int'],
            ['uploads', 'default_allowed_extensions', '["jpg","jpeg","png","pdf"]', 'json'],
            ['tracking', 'public_tracking_verification', 'passport_number', 'string'],
        ];

        foreach ($defaults as [$group, $key, $value, $type]) {
            Setting::firstOrCreate(['key' => $key], compact('group', 'value', 'type'));
        }
    }
}
