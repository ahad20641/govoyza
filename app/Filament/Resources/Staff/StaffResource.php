<?php

namespace App\Filament\Resources\Staff;

use App\Enums\UserType;
use App\Filament\Resources\Staff\Pages\ListStaff;
use App\Filament\Resources\Staff\Tables\StaffTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Govoyza staff (Owner, Operations, Finance). New staff are created with `govoyza:create-admin`.
 * Admin console, group "Users". List-only shell from Sprint S1; task 1.5 — Auth and roles.
 * Access: staff.manage (Owner passes every check).
 */
class StaffResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Users';

    protected static ?string $navigationLabel = 'Staff';

    protected static ?string $modelLabel = 'Staff';

    protected static ?string $pluralModelLabel = 'Staff';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return StaffTable::configure($table);
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('staff.manage');
    }

    public static function canCreate(): bool
    {
        return false; // TODO: create/edit forms arrive with task 1.5
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', UserType::Admin)->with('role');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaff::route('/'),
        ];
    }
}
