<?php

namespace App\Filament\Resources\Partners;

use App\Filament\Resources\Partners\Pages\ListPartners;
use App\Filament\Resources\Partners\Pages\ViewPartner;
use App\Filament\Resources\Partners\Schemas\PartnerInfolist;
use App\Filament\Resources\Partners\Tables\PartnersTable;
use App\Models\Partner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Suppliers. Admin-only (CLAUDE.md rule 1): never exposed to customers.
 * Partners join through an invite (list page action), never created here.
 * Access: partners.manage (Operations, Owner).
 */
class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Partners';

    protected static ?string $recordTitleAttribute = 'legal_name';

    public static function infolist(Schema $schema): Schema
    {
        return PartnerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PartnersTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartners::route('/'),
            'view' => ViewPartner::route('/{record}'),
        ];
    }
}
