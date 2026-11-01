<?php

namespace App\Filament\Partner\Pages;

use App\Enums\PartnerStatus;
use App\Enums\UserType;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * The partner's earnings: amounts due per completed job and payout history.
 * Partner portal. Task 1.27 (payables), Phase 2 — Weekly payouts. S1 shell.
 */
class Earnings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Money';

    protected static ?string $title = 'Earnings & payouts';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->type === UserType::PartnerUser && $user->partner?->status === PartnerStatus::Active;
    }

    public function content(Schema $schema): Schema
    {
        $payouts = auth()->user()->partner->payouts()->latest()->limit(20)->get();

        return $schema->components([
            Section::make('Payouts')->schema($payouts->isEmpty()
                ? [Text::make('No payouts yet.')]
                : $payouts->map(fn ($p) => Text::make("{$p->period}: {$p->total->format()} ({$p->status->label()})"))->all()),
            Section::make('Coming in task 1.27')->schema([
                Text::make('Amounts due per completed job, holding period and the weekly payout batch.'),
            ]),
        ]);
    }
}
