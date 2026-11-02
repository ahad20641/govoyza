<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Nationality;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/** Product admin form, including its document checklist. Admin catalogue, task 1.11. */
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
            Section::make('Document requirements')
                ->description('Documents and file rules shown on the public service page and used for future uploads.')
                ->schema([
                    Repeater::make('requirements')
                        ->relationship()
                        ->defaultItems(0)
                        ->orderColumn('sort_order')
                        ->schema([
                            TextInput::make('label')->required()->maxLength(255),
                            Select::make('doc_type')->options(self::documentTypes())->searchable()->required(),
                            Toggle::make('is_required')->label('Required')->default(true),
                            CheckboxList::make('rules.formats')
                                ->label('Accepted formats')
                                ->options(['pdf' => 'PDF', 'jpg' => 'JPG', 'jpeg' => 'JPEG', 'png' => 'PNG'])
                                ->columns(4),
                            TextInput::make('rules.max_mb')->label('Maximum file size (MB)')
                                ->numeric()->integer()->minValue(1)->maxValue(100),
                            TextInput::make('rules.passport_valid_months')->label('Passport validity required (months)')
                                ->numeric()->integer()->minValue(0)->maxValue(120),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Document requirement'),
                ]),
        ]);
    }

    /** @return array<string, string> */
    private static function documentTypes(): array
    {
        return [
            'passport' => 'Passport',
            'photo' => 'Photo',
            'bank_statement' => 'Bank statement',
            'flight_itinerary' => 'Flight itinerary',
            'accommodation' => 'Proof of accommodation',
            'travel_insurance' => 'Travel insurance',
            'invitation_letter' => 'Invitation letter',
            'employment_letter' => 'Employment letter',
            'other' => 'Other',
        ];
    }
}
