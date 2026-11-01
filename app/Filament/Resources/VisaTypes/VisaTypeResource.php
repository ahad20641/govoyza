<?php

namespace App\Filament\Resources\VisaTypes;

use App\Filament\Resources\VisaTypes\Pages\EditVisaType;
use App\Filament\Resources\VisaTypes\Pages\ListVisaTypes;
use App\Filament\Resources\VisaTypes\Schemas\VisaTypeForm;
use App\Filament\Resources\VisaTypes\Tables\VisaTypesTable;
use App\Models\VisaType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The 7 visa purposes from the seeder. Slugs are fixed (they're in URLs);
 * admins edit name, description, order and active. Access: catalog.manage.
 */
class VisaTypeResource extends Resource
{
    protected static ?string $model = VisaType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return VisaTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VisaTypesTable::configure($table);
    }

    /** Reference data is public to read, but this admin screen is for catalogue staff only. */
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('create', VisaType::class);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVisaTypes::route('/'),
            'edit' => EditVisaType::route('/{record}/edit'),
        ];
    }
}
