<?php

namespace App\Models;

use App\Enums\RoleSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public static function forSlug(RoleSlug $slug): self
    {
        return static::where('slug', $slug->value)->firstOrFail();
    }

    public function slugEnum(): ?RoleSlug
    {
        return RoleSlug::tryFrom($this->slug);
    }
}
