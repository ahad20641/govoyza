<?php

namespace App\Filament\Resources\JournalEntries\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Columns for JournalEntryResource. Sprint S1. */
class JournalEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('posted_at')->dateTime()->sortable(),
                TextColumn::make('event')->searchable(),
                TextColumn::make('memo')->limit(60)->placeholder('—'),
                TextColumn::make('lines_count')->label('Lines'),
            ])
            ->emptyStateDescription('Coming in task 1.24.');
    }
}
