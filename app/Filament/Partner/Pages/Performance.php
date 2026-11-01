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
 * The partner's scores: on-time rate, rejection rate, rating. Routing prefers better scores.
 * Partner portal. Task 1.29 (SLA timers), Phase 3 — Smart routing. S1 shell.
 */
class Performance extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Jobs';

    protected static ?string $title = 'Performance';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->type === UserType::PartnerUser && $user->partner?->status === PartnerStatus::Active;
    }

    public function content(Schema $schema): Schema
    {
        $partner = auth()->user()->partner;

        return $schema->components([
            Section::make('Scores')->schema([
                Text::make('SLA score: '.($partner->sla_score !== null ? $partner->sla_score.'/100' : 'not rated yet')),
                Text::make('Open jobs: '.$partner->serviceJobs()->whereNull('closed_at')->count()),
            ]),
            Section::make('Coming in task 1.29 and Phase 3')->schema([
                Text::make('On-time rate, rejection rate and customer rating, month by month.'),
            ]),
        ]);
    }
}
