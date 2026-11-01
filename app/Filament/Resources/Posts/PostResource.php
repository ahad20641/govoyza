<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\Tables\PostsTable;
use App\Models\Post;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Blog and visa-news posts.
 * Admin console, group "CMS". List-only shell from Sprint S1; task 1.15 (CMS), Phase 3 (AI-drafted news).
 * Access: cms.manage (Owner passes every check).
 */
class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    protected static ?string $navigationLabel = 'Blog posts';

    protected static ?string $modelLabel = 'Blog post';

    protected static ?string $pluralModelLabel = 'Blog posts';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return PostsTable::configure($table);
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
            'index' => ListPosts::route('/'),
        ];
    }
}
