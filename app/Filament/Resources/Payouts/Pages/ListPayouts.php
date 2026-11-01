<?php

namespace App\Filament\Resources\Payouts\Pages;

use App\Filament\Resources\Payouts\PayoutResource;
use Filament\Resources\Pages\ListRecords;

/** List page for PayoutResource. Sprint S1. */
class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;
}
