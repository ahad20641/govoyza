<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * All orders. Status changes will be state-machine actions here (task 1.18); reassign supplier (1.20) and refunds (1.26) too.
 * Admin console, group "Orders". List-only shell from Sprint S1; task 1.18 — Order state machine (actions), 1.30 — Today board.
 * Access: applications.view_any (Owner passes every check).
 */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Orders';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?string $modelLabel = 'Order';

    protected static ?string $pluralModelLabel = 'Orders';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('applications.view_any');
    }

    public static function canCreate(): bool
    {
        return false; // TODO: create/edit forms arrive with task 1.18
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['product', 'user', 'agency']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
        ];
    }
}
