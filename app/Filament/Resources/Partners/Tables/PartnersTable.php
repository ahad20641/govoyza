<?php

namespace App\Filament\Resources\Partners\Tables;

use App\Enums\PartnerStatus;
use App\Models\Partner;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PartnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('legal_name')->label('Partner')->searchable(),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (PartnerStatus $state) => $state->label())
                    ->color(fn (PartnerStatus $state) => match ($state) {
                        PartnerStatus::Active => 'success',
                        PartnerStatus::Pending => 'warning',
                        PartnerStatus::Suspended => 'danger',
                    }),
                TextColumn::make('kyc_status')->label('KYC')->formatStateUsing(fn ($state) => $state->label()),
                IconColumn::make('ready')->label('Ready to approve')->boolean()
                    ->state(fn (Partner $record) => $record->onboardingGaps() === []),
                TextColumn::make('created_at')->label('Joined')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(
                    collect(PartnerStatus::cases())->mapWithKeys(fn (PartnerStatus $s) => [$s->value => $s->label()])->all(),
                ),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
