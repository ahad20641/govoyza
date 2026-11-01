<?php

namespace App\Filament\Resources\Countries;

use App\Filament\Resources\Countries\Pages\EditCountry;
use App\Filament\Resources\Countries\Pages\ListCountries;
use App\Filament\Resources\Countries\Schemas\CountryForm;
use App\Filament\Resources\Countries\Tables\CountriesTable;
use App\Models\Country;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Countries come from the reference data seeder (names, codes, regions); admins
 * mark destinations and write the public description/SEO text. No create/delete.
 * Access: catalog.manage (Operations, Owner).
 */
class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CountryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountriesTable::configure($table);
    }

    /** Reference data is public to read, but this admin screen is for catalogue staff only. */
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('create', Country::class);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountries::route('/'),
            'edit' => EditCountry::route('/{record}/edit'),
        ];
    }
}
