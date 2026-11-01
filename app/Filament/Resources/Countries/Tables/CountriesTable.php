<?php

namespace App\Filament\Resources\Countries\Tables;

use App\Enums\Continent;
use App\Enums\CountryRegion;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CountriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('flag')->label(''),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('iso2')->label('ISO')->searchable(),
                TextColumn::make('region')->formatStateUsing(fn (?CountryRegion $state) => $state?->label())->placeholder('—'),
                TextColumn::make('continent')->formatStateUsing(fn (?Continent $state) => $state?->label())->toggleable(),
                IconColumn::make('is_destination')->label('Destination')->boolean(),
                IconColumn::make('is_popular')->label('Popular')->boolean(),
                IconColumn::make('is_active')->label('Active')->boolean()->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_destination')->label('Destination'),
                SelectFilter::make('region')->options(
                    collect(CountryRegion::cases())->mapWithKeys(fn (CountryRegion $r) => [$r->value => $r->label()])->all(),
                ),
                SelectFilter::make('continent')->options(
                    collect(Continent::cases())->mapWithKeys(fn (Continent $c) => [$c->value => $c->label()])->all(),
                ),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
