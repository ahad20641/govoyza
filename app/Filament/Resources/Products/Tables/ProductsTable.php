<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('public_name')
            ->columns([
                TextColumn::make('public_name')->searchable()->sortable(),
                TextColumn::make('country.name')->label('Destination')->sortable(),
                TextColumn::make('visaType.name')->label('Visa type')->sortable(),
                TextColumn::make('nationality_iso2')->label('Nationality')->placeholder('Any'),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => $state instanceof ProductStatus ? ucfirst($state->value) : ucfirst((string) $state)),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}
