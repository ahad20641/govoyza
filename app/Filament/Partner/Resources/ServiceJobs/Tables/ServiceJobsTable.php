<?php

namespace App\Filament\Partner\Resources\ServiceJobs\Tables;

use App\Models\ServiceJob;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Columns for the partner's job list: order number and applicant names only (CLAUDE.md rule 2). Sprint S1. */
class ServiceJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('assigned_at', 'desc')
            ->columns([
                TextColumn::make('order.number')->label('Order'),
                TextColumn::make('applicants')->label('Applicants')
                    ->state(fn (ServiceJob $record) => $record->order->applicants->map->fullName()->join(', ')),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('sla_due_at')->label('Due')->dateTime()->sortable(),
                TextColumn::make('assigned_at')->dateTime()->sortable(),
            ])
            ->recordActions([ViewAction::make()])
            ->emptyStateDescription('Jobs appear here when GoVoyza assigns you an order (task 1.20).');
    }
}
