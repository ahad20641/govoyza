<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A CMS page (About, Terms, Privacy, Refund, …) edited in the admin. Public site.
 * Task 1.15 — Static/CMS pages.
 */
class Page extends Model
{
    protected $fillable = ['slug', 'title', 'body', 'seo_title', 'seo_description', 'is_published'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
