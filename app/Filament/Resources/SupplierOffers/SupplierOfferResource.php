<?php

namespace App\Filament\Resources\SupplierOffers;

use App\Filament\Resources\SupplierOffers\Pages\ListSupplierOffers;
use App\Filament\Resources\SupplierOffers\Tables\SupplierOffersTable;
use App\Models\SupplierOffer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Every partner offer behind the catalogue, with cost. Admin only: supplier details never leave admin/partner contexts (CLAUDE.md rule 1). Review happens in the Offer review queue.
 * Admin console, group "Catalogue". List-only shell from Sprint S1; task 1.9 — Products and supplier offers.
 * Access: catalog.manage (Owner passes every check).
 */
class SupplierOfferResource extends Resource
{
    protected static ?string $model = SupplierOffer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Supplier offers';

    protected static ?string $modelLabel = 'Supplier offer';

    protected static ?string $pluralModelLabel = 'Supplier offers';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return SupplierOffersTable::configure($table);
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('catalog.manage');
    }

    public static function canCreate(): bool
    {
        return false; // TODO: create/edit forms arrive with task 1.9
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['product', 'partner']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupplierOffers::route('/'),
        ];
    }
}
