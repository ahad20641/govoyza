<?php

namespace App\Filament\Resources\Agencies;

use App\Filament\Resources\Agencies\Pages\ListAgencies;
use App\Filament\Resources\Agencies\Tables\AgenciesTable;
use App\Models\Agency;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * B2B travel agencies: approval, tier, credit limit.
 * Admin console, group "Users". List-only shell from Sprint S1; task 1.5 (approval), Phase 2 — Tiers, wallet, credit.
 * Access: agencies.manage (Owner passes every check).
 */
class AgencyResource extends Resource
{
    protected static ?string $model = Agency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Users';

    protected static ?string $navigationLabel = 'Agencies';

    protected static ?string $modelLabel = 'Agency';

    protected static ?string $pluralModelLabel = 'Agencies';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return AgenciesTable::configure($table);
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('agencies.manage');
    }

    public static function canCreate(): bool
    {
        return false; // TODO: create/edit forms arrive with task 1.5
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('owner');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgencies::route('/'),
        ];
    }
}
