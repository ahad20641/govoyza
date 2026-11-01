<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\StaffResource;
use Filament\Resources\Pages\ListRecords;

/** List page for StaffResource. Sprint S1. */
class ListStaff extends ListRecords
{
    protected static string $resource = StaffResource::class;
}
