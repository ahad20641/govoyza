<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** How the visa is issued: e-visa or sticker (needs the physical passport, so courier/drop-off). */
class VisaFormat extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'requires_physical_passport', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['requires_physical_passport' => 'boolean', 'is_active' => 'boolean'];
    }
}
