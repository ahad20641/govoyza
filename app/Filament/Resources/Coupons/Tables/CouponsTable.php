<?php

namespace App\Filament\Resources\Coupons\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Columns for CouponResource. Sprint S1. */
class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('code')->searchable(),
                TextColumn::make('discount_type')->label('Type')->formatStateUsing(fn ($state) => ucfirst($state->value)),
                TextColumn::make('discount_value')->label('Value'),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('ends_at')->dateTime()->placeholder('No end'),
            ])
            ->emptyStateDescription('Coming in task 1.12.');
    }
}
