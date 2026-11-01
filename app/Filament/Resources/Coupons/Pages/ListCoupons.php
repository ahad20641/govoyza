<?php

namespace App\Filament\Resources\Coupons\Pages;

use App\Filament\Resources\Coupons\CouponResource;
use Filament\Resources\Pages\ListRecords;

/** List page for CouponResource. Sprint S1. */
class ListCoupons extends ListRecords
{
    protected static string $resource = CouponResource::class;
}
