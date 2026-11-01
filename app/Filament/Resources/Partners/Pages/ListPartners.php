<?php

namespace App\Filament\Resources\Partners\Pages;

use App\Filament\Resources\Partners\PartnerResource;
use App\Models\Partner;
use App\Models\User;
use App\Services\Partners\PartnerInvitations;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPartners extends ListRecords
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('invite')
                ->label('Invite partner')
                ->icon('heroicon-o-envelope')
                ->authorize('invite', Partner::class)
                ->schema([
                    TextInput::make('email')->email()->required()->maxLength(255)
                        ->rule(fn () => function (string $attribute, $value, \Closure $fail) {
                            if (User::withTrashed()->where('email', strtolower($value))->exists()) {
                                $fail('An account with this email already exists.');
                            }
                        }),
                    TextInput::make('legal_name')->label('Company name (optional)')->maxLength(255),
                ])
                ->action(function (array $data, PartnerInvitations $invitations) {
                    $invitations->invite($data['email'], $data['legal_name'] ?? null, auth()->user());
                    Notification::make()->title("Invitation sent to {$data['email']}")->success()->send();
                }),
        ];
    }
}
