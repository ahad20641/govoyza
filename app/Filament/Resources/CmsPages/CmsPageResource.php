<?php

namespace App\Filament\Resources\CmsPages;

use App\Filament\Resources\CmsPages\Pages\ListCmsPages;
use App\Filament\Resources\CmsPages\Tables\CmsPagesTable;
use App\Models\Page;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Public pages (About, Terms, Privacy, …). A published page replaces its placeholder on the site.
 * Admin console, group "CMS". List-only shell from Sprint S1; task 1.15 — Static/CMS pages.
 * Access: cms.manage (Owner passes every check).
 */
class CmsPageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    protected static ?string $navigationLabel = 'Pages';

    protected static ?string $modelLabel = 'Page';

    protected static ?string $pluralModelLabel = 'Pages';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return CmsPagesTable::configure($table);
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('cms.manage');
    }

    public static function canCreate(): bool
    {
        return false; // TODO: create/edit forms arrive with task 1.15
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCmsPages::route('/'),
        ];
    }
}
