<?php

namespace App\Filament\Partner\Resources\ServiceJobs\Pages;

use App\Filament\Partner\Resources\ServiceJobs\ServiceJobResource;
use Filament\Resources\Pages\ListRecords;

/** Partner's assigned jobs list. Partner portal. Task 1.20 (S1 shell). */
class ListServiceJobs extends ListRecords
{
    protected static string $resource = ServiceJobResource::class;
}
