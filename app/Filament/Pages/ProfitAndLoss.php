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
 * Profit and loss by month and per-order profit, with CSV export.
 * Admin console, group "Accounting". Shell from Sprint S1; task 1.27 — Basic reports. Access: reports.view_all (Owner passes every check).
 */
class ProfitAndLoss extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?string $title = 'Profit & loss';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('reports.view_all');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Coming in task 1.27')->schema([
                Text::make('Monthly P&L from the ledger, per-order profit and CSV export.'),
            ]),
        ]);
    }
}
