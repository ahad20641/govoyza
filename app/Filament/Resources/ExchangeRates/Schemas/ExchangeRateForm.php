<?php

namespace App\Filament\Resources\ExchangeRates\Schemas;

use App\Enums\Currency;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class ExchangeRateForm
{
    public static function configure(Schema $schema): Schema
    {
        $currencies = collect(Currency::cases())->mapWithKeys(fn (Currency $c) => [$c->value => $c->value])->all();

        return $schema
            ->components([
                Select::make('base')
                    ->label('From')
                    ->options($currencies)
                    ->required()
                    ->different('quote'),
                Select::make('quote')
                    ->label('To')
                    ->options($currencies)
                    ->required(),
                // Kept as a string end to end: a rate is exact decimal maths (brick/math), never a float.
                TextInput::make('rate')
                    ->helperText('How many "To" units one "From" unit buys, e.g. 1 USD = 122.50 BDT.')
                    ->required()
                    ->regex('/^\d{1,10}(\.\d{1,8})?$/')
                    ->rule('not_regex:/^0+(\.0+)?$/'),
                TextInput::make('buffer_pct')
                    ->label('Buffer %')
                    ->helperText('Safety margin added on top of the rate when converting selling prices.')
                    ->required()
                    ->default('0')
                    ->regex('/^\d{1,2}(\.\d{1,2})?$/'),
                DateTimePicker::make('effective_at')
                    ->label('Takes effect')
                    ->required()
                    ->default(now())
                    ->seconds(false)
                    ->unique(
                        table: 'exchange_rates',
                        column: 'effective_at',
                        modifyRuleUsing: fn (Unique $rule, callable $get) => $rule
                            ->where('base', $get('base'))
                            ->where('quote', $get('quote')),
                    ),
            ]);
    }
}
