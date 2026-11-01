<?php

namespace App\Filament\Resources\Payouts;

use App\Filament\Resources\Payouts\Pages\ListPayouts;
use App\Filament\Resources\Payouts\Tables\PayoutsTable;
use App\Models\Payout;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Partner payout batches: draft → approved (one click) → paid.
 * Admin console, group "Accounting". List-only shell from Sprint S1; task 1.27 (payables), Phase 2 — Weekly payout batch.
 * Access: payments.manage (Owner passes every check).
 */
class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    protected static ?string $navigationLabel = 'Payouts';

    protected static ?string $modelLabel = 'Payout';

    protected static ?string $pluralModelLabel = 'Payouts';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return PayoutsTable::configure($table);
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('payments.manage');
    }

    public static function canCreate(): bool
    {
        return false; // TODO: create/edit forms arrive with task 1.27
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('partner');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayouts::route('/'),
        ];
    }
}
