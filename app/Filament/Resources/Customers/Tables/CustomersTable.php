<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Columns for CustomerResource. Sprint S1. */
class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => ucfirst($state->value)),
                TextColumn::make('created_at')->label('Joined')->date()->sortable(),
            ])
            ->emptyStateDescription('Coming in task 1.22.');
    }
}
