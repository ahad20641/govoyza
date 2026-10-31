<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyDocument extends Model
{
    protected $fillable = [
        'document_type', 'disk', 'path', 'original_name', 'mime_type', 'size', 'sha256', 'status',
    ];

    protected $hidden = ['path', 'disk'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
