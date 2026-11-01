<?php

namespace App\Filament\Resources\SupplierOfferReviews\Tables;

use App\Enums\OfferRevisionStatus;
use App\Enums\SupplierOfferStatus;
use App\Models\SupplierOffer;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupplierOfferReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'asc')
            ->columns([
                TextColumn::make('review_type')->label('Queue item')->state(fn (SupplierOffer $record) => $record->status === SupplierOfferStatus::Pending
                    ? 'New offer'
                    : 'Live offer revision')->badge(),
                TextColumn::make('product.public_name')->label('Product')->searchable(),
                TextColumn::make('product.country.name')->label('Destination'),
                TextColumn::make('product.visaType.name')->label('Visa type'),
                TextColumn::make('partner.legal_name')->label('Partner')->searchable(),
                TextColumn::make('pending_cost_change')->label('Cost change')->state(function (SupplierOffer $record): string {
                    $revision = $record->revisions->first(fn ($revision) => $revision->status === OfferRevisionStatus::Pending);

                    return $revision?->isCostIncrease() ? 'Increase' : 'No increase';
                })->badge()->color(fn (string $state) => $state === 'Increase' ? 'warning' : 'gray'),
                TextColumn::make('updated_at')->label('Submitted')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    SupplierOfferStatus::Pending->value => 'New offer',
                    SupplierOfferStatus::Live->value => 'Live offer revision',
                ]),
            ])
            ->recordActions([ViewAction::make()]);
    }
}
