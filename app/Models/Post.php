<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A blog / visa-news post. Public site, edited in the admin CMS. Task 1.15 (CMS), Phase 3 (AI drafts).
 */
class Post extends Model
{
    protected $fillable = ['slug', 'title', 'excerpt', 'body', 'published_at', 'seo_title', 'seo_description'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}
