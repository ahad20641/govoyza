<?php

namespace App\Filament\Partner\Resources\SupplierOffers;

use App\Enums\PartnerStatus;
use App\Enums\UserType;
use App\Filament\Partner\Resources\SupplierOffers\Pages\CreateSupplierOffer;
use App\Filament\Partner\Resources\SupplierOffers\Pages\EditSupplierOffer;
use App\Filament\Partner\Resources\SupplierOffers\Pages\ListSupplierOffers;
use App\Filament\Partner\Resources\SupplierOffers\Schemas\SupplierOfferForm;
use App\Filament\Partner\Resources\SupplierOffers\Tables\SupplierOffersTable;
use App\Models\SupplierOffer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SupplierOfferResource extends Resource
{
    protected static ?string $model = SupplierOffer::class;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Offers';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return SupplierOfferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierOffersTable::configure($table);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->type === UserType::PartnerUser
            && $user->partner?->status === PartnerStatus::Active;
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return (bool) auth()->user()?->can('update', $record);
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['product.country', 'product.visaType', 'revisions']);
        $partnerId = auth()->user()?->partner_id;

        return $partnerId === null ? $query->whereRaw('1 = 0') : $query->where('partner_id', $partnerId);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupplierOffers::route('/'),
            'create' => CreateSupplierOffer::route('/create'),
            'edit' => EditSupplierOffer::route('/{record}/edit'),
        ];
    }
}
