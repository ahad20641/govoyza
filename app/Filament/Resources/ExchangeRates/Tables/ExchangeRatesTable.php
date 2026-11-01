<?php

namespace App\Filament\Resources\ExchangeRates\Tables;

use App\Enums\Currency;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExchangeRatesTable
{
    public static function configure(Table $table): Table
    {
        $currencies = collect(Currency::cases())->mapWithKeys(fn (Currency $c) => [$c->value => $c->value])->all();

        return $table
            ->defaultSort('effective_at', 'desc')
            ->columns([
                TextColumn::make('base')->label('From'),
                TextColumn::make('quote')->label('To'),
                TextColumn::make('rate'),
                TextColumn::make('buffer_pct')->label('Buffer %'),
                TextColumn::make('effective_at')->label('Takes effect')->dateTime()->sortable(),
                TextColumn::make('setter.name')->label('Set by'),
            ])
            ->filters([
                SelectFilter::make('base')->label('From')->options($currencies),
                SelectFilter::make('quote')->label('To')->options($currencies),
            ]);
    }
}
