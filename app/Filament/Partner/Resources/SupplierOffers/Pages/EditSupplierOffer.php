<?php

namespace App\Filament\Partner\Resources\SupplierOffers\Pages;

use App\Enums\Currency;
use App\Filament\Partner\Resources\SupplierOffers\SupplierOfferResource;
use App\Models\OfferRevision;
use App\Services\Catalogue\SupplierOfferWorkflow;
use App\Support\Money\Money;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSupplierOffer extends EditRecord
{
    protected static string $resource = SupplierOfferResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['cost_amount'] = $this->record->cost?->toDecimal() ?? '0.00';
        $data['currency'] = $this->record->currency;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['cost_minor'] = Money::of((string) $data['cost_amount'], Currency::from($data['currency']))->minor;
        unset($data['cost_amount']);

        $result = app(SupplierOfferWorkflow::class)->updateOffer(auth()->user()->partner, $record, $data);

        return $result instanceof OfferRevision ? $record->refresh() : $result;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
