<?php

namespace App\Models;

use App\Enums\PartnerDocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** KYC file on the private disk. Written only by PartnerOnboarding. */
class PartnerDocument extends Model
{
    protected $guarded = ['*'];

    protected $hidden = ['path', 'disk'];

    protected function casts(): array
    {
        return ['document_type' => PartnerDocumentType::class];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
