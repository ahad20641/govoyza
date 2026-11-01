<?php

namespace App\Filament\Resources\Payouts\Tables;

use App\Models\Payout;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Columns for PayoutResource. Sprint S1. */
class PayoutsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('partner.legal_name')->label('Partner'),
                TextColumn::make('period'),
                TextColumn::make('total')->state(fn (Payout $record) => $record->total?->format()),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => ucfirst($state->value)),
            ])
            ->emptyStateDescription('Coming in task 1.27.');
    }
}
