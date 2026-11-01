<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Columns for OrderResource. Sprint S1. */
class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('number')->searchable(),
                TextColumn::make('product.public_name')->label('Product'),
                TextColumn::make('user.name')->label('Buyer')->description(fn (Order $record) => $record->agency?->name),
                TextColumn::make('channel')->formatStateUsing(fn ($state) => strtoupper($state->value)),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('total')->state(fn (Order $record) => $record->total?->format()),
                TextColumn::make('placed_at')->dateTime()->sortable(),
            ])
            ->emptyStateDescription('Coming in task 1.18.');
    }
}
