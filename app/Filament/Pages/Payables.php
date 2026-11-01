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
 * What Govoyza owes each partner and when it falls due (after the holding period).
 * Admin console, group "Accounting". Shell from Sprint S1; task 1.27 — Basic reports. Access: reports.view_all (Owner passes every check).
 */
class Payables extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?string $title = 'Supplier payables';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('reports.view_all');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Coming in task 1.27')->schema([
                Text::make('Payables due by partner, ageing and the list that feeds the weekly payout batch.'),
            ]),
        ]);
    }
}
