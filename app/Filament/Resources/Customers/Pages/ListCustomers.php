<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\ListRecords;

/** List page for CustomerResource. Sprint S1. */
class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;
}
