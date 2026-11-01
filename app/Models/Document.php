<?php

namespace App\Models;

use App\Enums\DocumentCheckStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An applicant's uploaded file, versioned. Lives on the private disk and is only ever served
 * through a signed temporary URL (CLAUDE.md rule 6). Task 1.19 — Document upload & review.
 */
class Document extends Model
{
    protected $fillable = [
        'requirement_id', 'disk', 'path', 'original_name', 'mime_type', 'size', 'version', 'uploaded_by',
    ];

    // The storage path is an internal detail; links are built by the download controller.
    protected $hidden = ['disk', 'path'];

    protected function casts(): array
    {
        return [
            'check_status' => DocumentCheckStatus::class,
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
