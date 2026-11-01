<?php

namespace App\Filament\Resources\JournalEntries\Pages;

use App\Filament\Resources\JournalEntries\JournalEntryResource;
use Filament\Resources\Pages\ListRecords;

/** List page for JournalEntryResource. Sprint S1. */
class ListJournalEntries extends ListRecords
{
    protected static string $resource = JournalEntryResource::class;
}
