<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One item on a product's document checklist (doc type, required, file rules).
 * Admin catalogue, public service page, customer uploads. Task 1.11 — Requirements checklist.
 */
class Requirement extends Model
{
    protected $fillable = ['label', 'doc_type', 'is_required', 'rules', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'rules' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
