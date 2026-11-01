<?php

namespace App\Models;

use App\Enums\ServiceJobStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A partner's progress note on a job (status, appointment date, result file). Each one will fire
 * a customer notification through events. Partner portal. Task 1.21 — Partner job updates.
 */
class ServiceJobUpdate extends Model
{
    protected $fillable = ['status', 'note', 'appointment_at', 'result_document_id', 'created_by'];

    protected function casts(): array
    {
        return [
            'status' => ServiceJobStatus::class,
            'appointment_at' => 'datetime',
        ];
    }

    public function serviceJob(): BelongsTo
    {
        return $this->belongsTo(ServiceJob::class);
    }

    public function resultDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'result_document_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
