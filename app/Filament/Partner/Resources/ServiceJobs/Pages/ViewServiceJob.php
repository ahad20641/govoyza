<?php

namespace App\Filament\Partner\Resources\ServiceJobs\Pages;

use App\Filament\Partner\Resources\ServiceJobs\ServiceJobResource;
use Filament\Resources\Pages\ViewRecord;

/** Job detail for the partner. Partner portal. Tasks 1.20, 1.21 (S1 shell; updates and files come later). */
class ViewServiceJob extends ViewRecord
{
    protected static string $resource = ServiceJobResource::class;
}
