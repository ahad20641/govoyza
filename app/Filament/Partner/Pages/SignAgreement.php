<?php

namespace App\Filament\Partner\Pages;

use App\Filament\Partner\Pages\Concerns\PartnerFormPage;
use App\Services\Partners\PartnerAgreement;
use App\Services\Partners\PartnerOnboarding;
use BackedEnum;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

/** Click-to-sign: typed full name + timestamp + IP + agreement version + hash of the exact text. */
class SignAgreement extends Page
{
    use PartnerFormPage;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $navigationLabel = 'Partner agreement';

    protected static ?string $title = 'Partner agreement';

    protected static ?int $navigationSort = 3;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->label('Your full name, as your signature')
                    ->required()
                    ->maxLength(150),
                Checkbox::make('accept')
                    ->label('I have read this agreement and I sign it on behalf of my company.')
                    ->accepted(),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        $agreement = app(PartnerAgreement::class);
        $partner = $this->partner();

        $status = $partner->hasSignedCurrentAgreement()
            ? Callout::make("Signed by {$partner->agreement_signed_name} on {$partner->agreement_signed_at->format('j M Y, H:i')}.")->success()
            : Callout::make('Not signed yet.')->warning();

        return $schema->components([
            $status,
            Section::make("Agreement (version {$agreement->version()})")
                ->schema([Html::make(new HtmlString($agreement->html()))]),
            Section::make('Sign')
                ->visible(! $partner->hasSignedCurrentAgreement())
                ->schema([$this->formComponent('Sign agreement')]),
        ]);
    }

    public function save(PartnerOnboarding $onboarding): void
    {
        if ($this->partner()->hasSignedCurrentAgreement()) {
            return;
        }

        $data = $this->form->getState();
        $onboarding->signAgreement($this->partner(), auth()->user(), $data['full_name'], request()->ip());

        Notification::make()->title('Agreement signed')->success()->send();
    }
}
