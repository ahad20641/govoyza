<?php

namespace App\Filament\Resources\SupplierOfferReviews;

use App\Enums\OfferRevisionStatus;
use App\Enums\SupplierOfferStatus;
use App\Filament\Resources\SupplierOfferReviews\Pages\ListSupplierOfferReviews;
use App\Filament\Resources\SupplierOfferReviews\Pages\ViewSupplierOffer;
use App\Filament\Resources\SupplierOfferReviews\Schemas\SupplierOfferReviewInfolist;
use App\Filament\Resources\SupplierOfferReviews\Tables\SupplierOfferReviewsTable;
use App\Models\SupplierOffer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SupplierOfferReviewResource extends Resource
{
    protected static ?string $model = SupplierOffer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Offer review queue';

    protected static ?string $recordTitleAttribute = 'id';

    public static function infolist(Schema $schema): Schema
    {
        return SupplierOfferReviewInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierOfferReviewsTable::configure($table);
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('reviewAny', SupplierOffer::class);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('review', $record);
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['partner', 'product.country', 'product.visaType', 'revisions'])
            ->where(function (Builder $query): void {
                $query->where('status', SupplierOfferStatus::Pending)
                    ->orWhereHas('revisions', fn (Builder $revisions) => $revisions->where('status', OfferRevisionStatus::Pending));
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupplierOfferReviews::route('/'),
            'view' => ViewSupplierOffer::route('/{record}'),
        ];
    }
}
