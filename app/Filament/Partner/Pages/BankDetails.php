<?php

namespace App\Filament\Partner\Pages;

use App\Filament\Partner\Pages\Concerns\PartnerFormPage;
use App\Services\Partners\PartnerOnboarding;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/** Where GoVoyza pays the partner. Stored encrypted (Partner::$casts['bank_details']). */
class BankDetails extends Page
{
    use PartnerFormPage;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $navigationLabel = 'Bank details';

    protected static ?string $title = 'Bank details';

    protected static ?int $navigationSort = 2;

    public function mount(): void
    {
        $this->form->fill($this->partner()->bank_details ?? []);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('account_name')->label('Account name')->required()->maxLength(150),
                TextInput::make('bank_name')->label('Bank')->required()->maxLength(150),
                TextInput::make('branch')->maxLength(150),
                TextInput::make('account_number')->label('Account number')->required()->regex('/^[0-9]{6,20}$/')
                    ->validationMessages(['regex' => 'Digits only, 6 to 20 of them.']),
                TextInput::make('routing_number')->label('Routing number')->regex('/^[0-9]{9}$/')
                    ->validationMessages(['regex' => 'A routing number is 9 digits.']),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payout account')
                ->description('Payouts go only to an account in your company\'s name.')
                ->schema([$this->formComponent('Save bank details')]),
        ]);
    }

    public function save(PartnerOnboarding $onboarding): void
    {
        $onboarding->saveBankDetails($this->partner(), $this->form->getState());

        Notification::make()->title('Bank details saved')->success()->send();
    }
}
