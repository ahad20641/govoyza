<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\ServiceJobStatus;
use Database\Factories\ServiceJobFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Work assigned to a partner for one order (`jobs` is the queue table, hence ServiceJob).
 * Partner portal and admin. Tasks 1.20 — Routing & jobs, 1.21 — Partner job updates.
 *
 * Partners see the order number, applicant names and assigned documents only, and lose
 * access once `closed_at` is set (CLAUDE.md rule 2).
 */
class ServiceJob extends Model
{
    /** @use HasFactory<ServiceJobFactory> */
    use HasFactory;

    protected $fillable = ['sla_due_at'];

    // The cost snapshot is supplier pricing: admin and the owning partner only.
    protected $hidden = ['cost_snapshot_minor', 'currency'];

    protected function casts(): array
    {
        return [
            'status' => ServiceJobStatus::class,
            'cost_snapshot' => MoneyCast::class.':cost_snapshot_minor,currency',
            'sla_due_at' => 'datetime',
            'assigned_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    // ==================== Relationships ====================

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function supplierOffer(): BelongsTo
    {
        return $this->belongsTo(SupplierOffer::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ServiceJobUpdate::class);
    }

    // ==================== Scopes ====================

    public function scopeForPartner(Builder $query, ?int $partnerId): Builder
    {
        return $partnerId === null ? $query->whereRaw('1 = 0') : $query->where('partner_id', $partnerId);
    }
}
