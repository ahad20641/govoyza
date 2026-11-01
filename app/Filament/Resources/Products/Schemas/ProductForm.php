<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Nationality;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Visa product')->columns(2)->schema([
                Select::make('country_id')
                    ->relationship('country', 'name', modifyQueryUsing: fn ($query) => $query->destinations()->orderBy('name'))
                    ->searchable()->preload()->required(),
                Select::make('visa_type_id')
                    ->relationship('visaType', 'name', modifyQueryUsing: fn ($query) => $query->active())
                    ->searchable()->preload()->required(),
                Select::make('nationality_iso2')
                    ->label('Applicant nationality')
                    ->placeholder('Any nationality')
                    ->options(fn () => Nationality::query()->active()->with('country')->get()
                        ->mapWithKeys(fn (Nationality $nationality) => [$nationality->country->iso2 => $nationality->demonym])
                        ->all())
                    ->searchable(),
                TextInput::make('public_name')->required()->maxLength(255),
                TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(255),
                TextInput::make('validity')->maxLength(100),
                TextInput::make('processing_days_min')->label('Minimum processing days')->numeric()->integer()->minValue(1),
                TextInput::make('processing_days_max')->label('Maximum processing days')->numeric()->integer()->minValue(1),
                Textarea::make('description')->rows(4)->columnSpanFull(),
            ]),
        ]);
    }
}
