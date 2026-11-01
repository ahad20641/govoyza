<?php

namespace App\Filament\Resources\Customers;

use App\Enums\UserType;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Tables\CustomersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * B2C customer accounts.
 * Admin console, group "Users". List-only shell from Sprint S1; task 1.22 — Customer portal (admin view).
 * Access: clients.manage (Owner passes every check).
 */
class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static string|UnitEnum|null $navigationGroup = 'Users';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $modelLabel = 'Customer';

    protected static ?string $pluralModelLabel = 'Customers';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('clients.manage');
    }

    public static function canCreate(): bool
    {
        return false; // TODO: create/edit forms arrive with task 1.22
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', UserType::Customer);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
        ];
    }
}
