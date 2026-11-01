<?php

namespace App\Filament\Resources\VisaTypes\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VisaTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('slug')->label('URL slug (fixed)')->state(fn ($record) => $record->slug),
                TextInput::make('name')->required()->maxLength(100),
                Textarea::make('description')->rows(3)->maxLength(1000),
                TextInput::make('sort_order')->numeric()->minValue(0)->maxValue(65535)->required(),
                Toggle::make('is_active')->label('Active'),
            ]);
    }
}
