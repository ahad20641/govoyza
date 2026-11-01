<?php

namespace App\Filament\Resources\SupplierOffers\Tables;

use App\Models\SupplierOffer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Columns for SupplierOfferResource. Sprint S1. */
class SupplierOffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('product.public_name')->label('Product')->searchable(),
                TextColumn::make('partner.legal_name')->label('Partner')->searchable(),
                TextColumn::make('cost')->state(fn (SupplierOffer $record) => $record->cost?->format()),
                TextColumn::make('priority')->formatStateUsing(fn ($state) => ucfirst($state->value)),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state->value))),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->emptyStateDescription('Coming in task 1.9.');
    }
}
