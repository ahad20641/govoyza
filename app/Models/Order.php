<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\OrderChannel;
use App\Enums\OrderDecision;
use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A customer's or agency's purchase of one Product. Public site, customer and agent portals, admin.
 * Tasks 1.17 — Order placement, 1.18 — Order state machine.
 *
 * `status` is never set directly outside App\Domain\Orders\OrderStateMachine (CLAUDE.md rule 5),
 * and `price_snapshot` never changes after `placed`.
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, SoftDeletes;

    // status and price fields are not mass-assignable: the state machine and pricing engine set them.
    protected $fillable = ['product_id', 'travel_date'];

    protected function casts(): array
    {
        return [
            'channel' => OrderChannel::class,
            'status' => OrderStatus::class,
            'decision' => OrderDecision::class,
            'price_snapshot' => 'array',
            'total' => MoneyCast::class.':total_minor,currency',
            'travel_date' => 'date',
            'placed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class);
    }

    public function documents(): HasManyThrough
    {
        return $this->hasManyThrough(Document::class, Applicant::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** The current partner job. Reassignment closes the old one and opens a new one. */
    public function job(): HasOne
    {
        return $this->hasOne(ServiceJob::class)->latestOfMany();
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(ServiceJob::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    // ==================== Scopes ====================

    /** Orders a customer or agent may see in their portal. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $agency = $user->currentAgency();

        return $agency !== null ? $query->where('agency_id', $agency->id) : $query->where('user_id', $user->id);
    }

    public function scopeWithStatus(Builder $query, OrderStatus ...$statuses): Builder
    {
        return $query->whereIn('status', $statuses);
    }
}
