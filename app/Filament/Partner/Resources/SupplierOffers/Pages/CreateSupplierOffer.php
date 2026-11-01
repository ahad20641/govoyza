<?php

namespace App\Filament\Partner\Resources\SupplierOffers\Pages;

use App\Filament\Partner\Resources\SupplierOffers\SupplierOfferResource;
use App\Services\Catalogue\SupplierOfferWorkflow;
use App\Support\Money\Money;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSupplierOffer extends CreateRecord
{
    protected static string $resource = SupplierOfferResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['cost_minor'] = Money::of((string) $data['cost_amount'], $data['currency'])->minor;
        unset($data['cost_amount']);

        return app(SupplierOfferWorkflow::class)->createDraft(auth()->user()->partner, $data);
    }
}
