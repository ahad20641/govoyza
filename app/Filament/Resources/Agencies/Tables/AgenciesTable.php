<?php

namespace App\Filament\Resources\Agencies\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Columns for AgencyResource. Sprint S1. */
class AgenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('owner.email')->label('Owner'),
                TextColumn::make('tier')->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('created_at')->label('Registered')->date()->sortable(),
            ])
            ->emptyStateDescription('Coming in task 1.5.');
    }
}
