<?php

namespace App\Filament\Resources\CmsPages\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Columns for CmsPageResource. Sprint S1. */
class CmsPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('slug'),
                IconColumn::make('is_published')->label('Published')->boolean(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->emptyStateDescription('Coming in task 1.15.');
    }
}
