<?php

namespace App\Filament\Resources\SupplierOfferReviews\Schemas;

use App\Enums\OfferRevisionStatus;
use App\Models\OfferRevision;
use App\Models\SupplierOffer;
use App\Services\Catalogue\OfferReviewWorkflow;
use App\Support\Money\Money;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierOfferReviewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Offer under review')->columns(3)->schema([
                TextEntry::make('partner.legal_name')->label('Partner'),
                TextEntry::make('product.public_name')->label('Current product name'),
                TextEntry::make('product.country.name')->label('Destination'),
                TextEntry::make('product.visaType.name')->label('Visa type'),
                TextEntry::make('product.nationality_iso2')->label('Nationality')->placeholder('Any'),
                TextEntry::make('cost')->label('Supplier cost')->state(fn (SupplierOffer $record) => $record->cost?->format()),
                TextEntry::make('processing_days')->label('Processing days'),
                TextEntry::make('capacity_per_week')->label('Weekly capacity')->placeholder('Not specified'),
                TextEntry::make('priority')->formatStateUsing(fn ($state) => ucfirst($state->value)),
                TextEntry::make('inclusions')->listWithLineBreaks()->placeholder('None listed'),
                TextEntry::make('refund_terms')->placeholder('None listed')->columnSpanFull(),
            ]),
            Section::make('Comparable offers')->description('Offers for the same destination, visa type, and applicant nationality.')
                ->schema([
                    RepeatableEntry::make('comparable_offers')
                        ->hiddenLabel()
                        ->state(fn (SupplierOffer $record) => app(OfferReviewWorkflow::class)->comparableOffers($record)
                            ->map(fn (SupplierOffer $offer) => [
                                'partner_name' => $offer->partner->legal_name,
                                'product_name' => $offer->product->public_name,
                                'cost' => $offer->cost?->format() ?? '—',
                                'processing_days' => $offer->processing_days,
                                'priority' => ucfirst($offer->priority->value),
                                'status' => ucfirst($offer->status->value),
                                'under_review' => $offer->is($record) ? 'Under review' : '',
                            ])->all())
                        ->columns(3)
                        ->schema([
                            TextEntry::make('partner_name')->label('Partner'),
                            TextEntry::make('product_name')->label('Product'),
                            TextEntry::make('cost')->label('Cost'),
                            TextEntry::make('processing_days')->label('Processing days'),
                            TextEntry::make('priority')->label('Priority'),
                            TextEntry::make('status')->label('Status'),
                            TextEntry::make('under_review')->label(''),
                        ]),
                ]),
            Section::make('Pending revisions')->schema([
                RepeatableEntry::make('pending_revisions')
                    ->hiddenLabel()
                    ->state(fn (SupplierOffer $record) => $record->revisions
                        ->where('status', OfferRevisionStatus::Pending)
                        ->map(fn (OfferRevision $revision) => [
                            'submitted_at' => $revision->submitted_at?->format('j M Y H:i'),
                            'cost' => isset($revision->payload['cost_minor'], $revision->payload['currency'])
                                ? Money::ofMinor((int) $revision->payload['cost_minor'], $revision->payload['currency'])->format()
                                : 'No cost change',
                            'processing_days' => $revision->payload['processing_days'] ?? $record->processing_days,
                            'cost_change' => $revision->isCostIncrease() ? 'Cost increase' : 'No cost increase',
                        ])->values()->all())
                    ->columns(2)
                    ->schema([
                        TextEntry::make('submitted_at')->label('Submitted'),
                        TextEntry::make('cost')->label('Proposed cost'),
                        TextEntry::make('processing_days')->label('Processing days'),
                        TextEntry::make('cost_change')->label('Flag'),
                    ]),
            ])->visible(fn (SupplierOffer $record) => $record->revisions()->where('status', OfferRevisionStatus::Pending)->exists()),
            Section::make('Last review')->columns(2)->schema([
                TextEntry::make('review_comment')->placeholder('No review comment'),
                TextEntry::make('reviewer.name')->label('Reviewed by')->placeholder('Not reviewed'),
                TextEntry::make('reviewed_at')->dateTime()->placeholder('Not reviewed'),
            ])->visible(fn (SupplierOffer $record) => $record->reviewed_at !== null),
        ]);
    }
}
