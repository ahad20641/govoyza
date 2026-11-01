<?php

namespace App\Filament\Resources\SupplierOfferReviews\Pages;

use App\Domain\Catalogue\OfferReviewWorkflow;
use App\Enums\Currency;
use App\Enums\MarkupType;
use App\Enums\OfferPriority;
use App\Enums\OfferRevisionStatus;
use App\Enums\SupplierOfferStatus;
use App\Filament\Resources\SupplierOfferReviews\SupplierOfferReviewResource;
use App\Models\OfferRevision;
use App\Models\SupplierOffer;
use App\Support\Money\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;

/** @property SupplierOffer $record */
class ViewSupplierOffer extends ViewRecord
{
    protected static string $resource = SupplierOfferReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approveOffer')
                ->label('Approve offer')
                ->color('success')
                ->visible(fn () => $this->record->status === SupplierOfferStatus::Pending)
                ->schema([
                    TextInput::make('public_name')->default($this->record->product->public_name)->required()->maxLength(255),
                    Textarea::make('description')->default($this->record->product->description)->rows(3)->maxLength(5000),
                    Select::make('b2c_markup_type')->label('B2C markup type')->options(self::markupOptions())
                        ->live()->default($this->record->product->b2c_markup_type->value)->required(),
                    TextInput::make('b2c_markup_value')->label('B2C markup')
                        ->helperText(fn (Get $get) => $get('b2c_markup_type') === MarkupType::Fixed->value
                            ? 'Fixed amount in '.$this->record->product->currency
                            : 'Whole percentage from 0 to 100.')
                        ->numeric()->minValue(0)->required()
                        ->rules(fn (Get $get) => $get('b2c_markup_type') === MarkupType::Percent->value
                            ? ['integer', 'max:100'] : ['regex:/^\d+(\.\d{1,2})?$/'])
                        ->default($this->markupFormValue('b2c')),
                    Select::make('b2b_markup_type')->label('B2B markup type')->options(self::markupOptions())
                        ->live()->default($this->record->product->b2b_markup_type->value)->required(),
                    TextInput::make('b2b_markup_value')->label('B2B markup')
                        ->helperText(fn (Get $get) => $get('b2b_markup_type') === MarkupType::Fixed->value
                            ? 'Fixed amount in '.$this->record->product->currency
                            : 'Whole percentage from 0 to 100.')
                        ->numeric()->minValue(0)->required()
                        ->rules(fn (Get $get) => $get('b2b_markup_type') === MarkupType::Percent->value
                            ? ['integer', 'max:100'] : ['regex:/^\d+(\.\d{1,2})?$/'])
                        ->default($this->markupFormValue('b2b')),
                    Select::make('priority')->options(self::priorityOptions())->default(OfferPriority::Other->value)->required(),
                ])
                ->action(function (array $data, OfferReviewWorkflow $workflow): void {
                    $this->normalizeMarkup($data, 'b2c');
                    $this->normalizeMarkup($data, 'b2b');
                    $workflow->approveOffer($this->record, auth()->user(), $data);
                    Notification::make()->title('Offer approved and product published')->success()->send();
                }),
            Action::make('requestChanges')
                ->label('Request changes')
                ->visible(fn () => $this->record->status === SupplierOfferStatus::Pending)
                ->schema([Textarea::make('comment')->required()->maxLength(2000)])
                ->action(function (array $data, OfferReviewWorkflow $workflow): void {
                    $workflow->requestOfferChanges($this->record, auth()->user(), $data['comment']);
                    Notification::make()->title('Changes requested')->success()->send();
                }),
            Action::make('rejectOffer')
                ->label('Reject offer')
                ->color('danger')
                ->visible(fn () => $this->record->status === SupplierOfferStatus::Pending)
                ->schema([Textarea::make('comment')->required()->maxLength(2000)])
                ->action(function (array $data, OfferReviewWorkflow $workflow): void {
                    $workflow->rejectOffer($this->record, auth()->user(), $data['comment']);
                    Notification::make()->title('Offer rejected')->success()->send();
                }),
            Action::make('approveRevision')
                ->label('Approve revision')
                ->color('success')
                ->visible(fn () => $this->hasPendingRevision())
                ->schema([$this->pendingRevisionSelect()])
                ->action(function (array $data, OfferReviewWorkflow $workflow): void {
                    $workflow->approveRevision($this->record->revisions()->findOrFail($data['revision_id']), auth()->user());
                    Notification::make()->title('Revision approved; live terms updated')->success()->send();
                }),
            Action::make('requestRevisionChanges')
                ->label('Request revision changes')
                ->visible(fn () => $this->hasPendingRevision())
                ->schema([$this->pendingRevisionSelect(), Textarea::make('comment')->required()->maxLength(2000)])
                ->action(function (array $data, OfferReviewWorkflow $workflow): void {
                    $revision = $this->record->revisions()->findOrFail($data['revision_id']);
                    $workflow->requestRevisionChanges($revision, auth()->user(), $data['comment']);
                    Notification::make()->title('Revision changes requested')->success()->send();
                }),
            Action::make('rejectRevision')
                ->label('Reject revision')
                ->color('danger')
                ->visible(fn () => $this->hasPendingRevision())
                ->schema([$this->pendingRevisionSelect(), Textarea::make('comment')->required()->maxLength(2000)])
                ->action(function (array $data, OfferReviewWorkflow $workflow): void {
                    $revision = $this->record->revisions()->findOrFail($data['revision_id']);
                    $workflow->rejectRevision($revision, auth()->user(), $data['comment']);
                    Notification::make()->title('Revision rejected')->success()->send();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    private function hasPendingRevision(): bool
    {
        return $this->record->status === SupplierOfferStatus::Live
            && $this->record->revisions()->where('status', OfferRevisionStatus::Pending)->exists();
    }

    private function pendingRevisionSelect(): Select
    {
        return Select::make('revision_id')
            ->label('Revision')
            ->options(fn () => $this->record->revisions()
                ->where('status', OfferRevisionStatus::Pending)
                ->get()
                ->mapWithKeys(fn (OfferRevision $revision) => [
                    $revision->id => 'Revision #'.$revision->id.($revision->isCostIncrease() ? ' — cost increase' : ''),
                ]))
            ->required();
    }

    private function markupFormValue(string $channel): string|int
    {
        $type = $this->record->product->{$channel.'_markup_type'};
        $value = $this->record->product->{$channel.'_markup_value'};

        return $type === MarkupType::Fixed
            ? Money::ofMinor((int) $value, Currency::from($this->record->product->currency))->toDecimal()
            : (int) $value;
    }

    /** @param array<string, mixed> $data */
    private function normalizeMarkup(array &$data, string $channel): void
    {
        $typeKey = $channel.'_markup_type';
        $valueKey = $channel.'_markup_value';

        if (MarkupType::from($data[$typeKey]) === MarkupType::Fixed) {
            $data[$valueKey] = Money::of((string) $data[$valueKey], Currency::from($this->record->product->currency))->minor;
        } else {
            $data[$valueKey] = (int) $data[$valueKey];
        }
    }

    /** @return array<string, string> */
    private static function markupOptions(): array
    {
        return collect(MarkupType::cases())->mapWithKeys(fn (MarkupType $type) => [$type->value => ucfirst($type->value)])->all();
    }

    /** @return array<string, string> */
    private static function priorityOptions(): array
    {
        return collect(OfferPriority::cases())->mapWithKeys(fn (OfferPriority $priority) => [$priority->value => ucfirst($priority->value)])->all();
    }
}
