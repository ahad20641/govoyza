<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Payment gateways, notification templates, roles and branding.
 * Admin console, group "Settings". Shell from Sprint S1; task 1.23 (gateways), 1.28 (email templates). Access: settings.manage (Owner passes every check).
 */
class SystemSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $title = 'Settings';

    protected static ?int $navigationSort = 9;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('settings.manage');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payment gateways')->schema([Text::make('Coming in task 1.23: SSLCommerz and Stripe keys, sandbox switch.')]),
            Section::make('Email templates')->schema([Text::make('Coming in task 1.28: one editable template per lifecycle event.')]),
            Section::make('Roles')->schema([Text::make('Staff roles come from config/permissions.php (`govoyza:sync-permissions`).')]),
        ]);
    }
}
