<?php

namespace App\Filament\Partner\Pages\Concerns;

use App\Enums\UserType;
use App\Models\Partner;
use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;

/** Shared bits of the onboarding pages: the signed-in user's partner and a form with one save button. */
trait PartnerFormPage
{
    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->type === UserType::PartnerUser && $user->partner !== null;
    }

    protected function partner(): Partner
    {
        return auth()->user()->partner;
    }

    protected function formComponent(string $saveLabel): Form
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([Action::make('save')->label($saveLabel)->submit('save')]),
            ]);
    }
}
