<?php

namespace App\Filament\Resources\Staff\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Columns for StaffResource. Sprint S1. */
class StaffTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role.name')->label('Role'),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => ucfirst($state->value)),
                TextColumn::make('last_login_at')->dateTime()->placeholder('Never'),
            ])
            ->emptyStateDescription('Coming in task 1.5.');
    }
}
