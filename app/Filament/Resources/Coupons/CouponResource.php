<?php

namespace App\Filament\Resources\Coupons;

use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Filament\Resources\Coupons\Tables\CouponsTable;
use App\Models\Coupon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Promo codes, B2C-only by default. Applied by the pricing engine, never by hand.
 * Admin console, group "CMS". List-only shell from Sprint S1; task 1.12 — Pricing engine (applies them), 1.15 (admin screen).
 * Access: pricing.manage (Owner passes every check).
 */
class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    protected static ?string $navigationLabel = 'Coupons';

    protected static ?string $modelLabel = 'Coupon';

    protected static ?string $pluralModelLabel = 'Coupons';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return CouponsTable::configure($table);
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('pricing.manage');
    }

    public static function canCreate(): bool
    {
        return false; // TODO: create/edit forms arrive with task 1.12
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoupons::route('/'),
        ];
    }
}
