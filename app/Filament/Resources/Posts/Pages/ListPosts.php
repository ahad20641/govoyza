<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use Filament\Resources\Pages\ListRecords;

/** List page for PostResource. Sprint S1. */
class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;
}
