<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;

/** List page for OrderResource. Sprint S1. */
class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
