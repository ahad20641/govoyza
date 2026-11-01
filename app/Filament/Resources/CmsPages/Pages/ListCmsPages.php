<?php

namespace App\Filament\Resources\CmsPages\Pages;

use App\Filament\Resources\CmsPages\CmsPageResource;
use Filament\Resources\Pages\ListRecords;

/** List page for CmsPageResource. Sprint S1. */
class ListCmsPages extends ListRecords
{
    protected static string $resource = CmsPageResource::class;
}
