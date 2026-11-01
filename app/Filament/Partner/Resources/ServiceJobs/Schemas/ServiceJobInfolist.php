<?php

namespace App\Filament\Partner\Resources\ServiceJobs\Schemas;

use App\Models\ServiceJob;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

/** Job detail for the partner. Order number and applicant names only (CLAUDE.md rule 2). Sprint S1. */
class ServiceJobInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ==================== Job ====================
                Section::make('Job')->columns(2)->schema([
                    TextEntry::make('order.number')->label('Order'),
                    TextEntry::make('status')->formatStateUsing(fn ($state) => $state->label()),
                    TextEntry::make('applicants')->label('Applicants')
                        ->state(fn (ServiceJob $record) => $record->order->applicants->map->fullName()->all())
                        ->listWithLineBreaks(),
                    TextEntry::make('sla_due_at')->label('Due')->dateTime()->placeholder('—'),
                    TextEntry::make('cost_snapshot')->label('Your payout')
                        ->state(fn (ServiceJob $record) => $record->cost_snapshot?->format()),
                ]),

                // ==================== Documents ====================
                Section::make('Documents')->schema([
                    Text::make('Coming in task 1.20: the applicants\' documents, available only while this job is open.'),
                ]),

                // ==================== Updates ====================
                Section::make('Updates')->schema([
                    Text::make('Coming in task 1.21: submitted, appointment date and decision, with result files.'),
                ]),
            ]);
    }
}
