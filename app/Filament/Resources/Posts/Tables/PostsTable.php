<?php

namespace App\Filament\Resources\Posts\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Columns for PostResource. Sprint S1. */
class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('published_at')->dateTime()->placeholder('Draft')->sortable(),
            ])
            ->emptyStateDescription('Coming in task 1.15.');
    }
}
