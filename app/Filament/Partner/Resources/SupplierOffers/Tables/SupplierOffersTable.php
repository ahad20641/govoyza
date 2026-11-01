<?php

namespace App\Filament\Partner\Resources\SupplierOffers\Tables;

use App\Enums\SupplierOfferStatus;
use App\Filament\Partner\Resources\SupplierOffers\SupplierOfferResource;
use App\Models\SupplierOffer;
use App\Services\Catalogue\SupplierOfferWorkflow;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierOffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('product.public_name')->label('Product')->searchable()->sortable(),
                TextColumn::make('product.country.name')->label('Destination'),
                TextColumn::make('cost')->label('Your cost')->state(fn (SupplierOffer $record) => $record->cost?->format() ?? '—'),
                TextColumn::make('processing_days')->label('Processing days'),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => $state instanceof SupplierOfferStatus ? ucfirst($state->value) : ucfirst((string) $state)),
                TextColumn::make('revisions_count')->counts('revisions')->label('Revisions'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('submitForReview')
                    ->label('Submit for review')
                    ->requiresConfirmation()
                    ->visible(fn (SupplierOffer $record) => in_array($record->status, [SupplierOfferStatus::Draft, SupplierOfferStatus::Rejected], true))
                    ->action(function (SupplierOffer $record): void {
                        app(SupplierOfferWorkflow::class)->submitForReview(auth()->user()->partner, $record);
                        Notification::make()->title('Offer submitted for review')->success()->send();
                    }),
            ])
            ->recordUrl(fn (SupplierOffer $record) => SupplierOfferResource::getUrl('edit', ['record' => $record]));
    }
}
