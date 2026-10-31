<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'contact_email', 'contact_phone', 'address', 'status', 'rating', 'internal_notes'];

    protected $hidden = ['internal_notes', 'contact_email', 'contact_phone'];

    protected function casts(): array
    {
        return ['is_approved' => 'boolean', 'rating' => 'decimal:2', 'is_demo' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
