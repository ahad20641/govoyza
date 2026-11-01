<?php

namespace App\Filament\Resources\Countries\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('From reference data')
                    ->description('Kept up to date by the seeder on every deploy; not editable here.')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')->state(fn ($record) => "{$record->flag} {$record->name}"),
                        TextEntry::make('codes')->label('ISO codes')->state(fn ($record) => "{$record->iso2} / {$record->iso3}"),
                        TextEntry::make('region')->state(fn ($record) => $record->region?->label() ?? '—'),
                    ]),
                Section::make('Catalogue')->columns(3)->schema([
                    Toggle::make('is_destination')->label('Sell visas for this country'),
                    Toggle::make('is_popular')->label('Show as popular'),
                    Toggle::make('is_active')->label('Active'),
                    TextInput::make('sort_order')->numeric()->minValue(0)->maxValue(65535)->required(),
                ]),
                Section::make('Public page text')->schema([
                    Textarea::make('description')->rows(5)->maxLength(5000),
                    TextInput::make('seo_title')->label('SEO title')->maxLength(70),
                    Textarea::make('seo_description')->label('SEO description')->rows(2)->maxLength(320),
                ]),
            ]);
    }
}
