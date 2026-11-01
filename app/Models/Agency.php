<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\AgencyStatus;
use App\Enums\AgencyTier;
use Database\Factories\AgencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A B2B travel agency: owner user, sub-users, tier, wallet and credit limit. Agent portal and admin.
 * Tasks 1.5 (registration), Phase 2 (wallet, tiers, sub-users).
 */
class Agency extends Model
{
    /** @use HasFactory<AgencyFactory> */
    use HasFactory, SoftDeletes;

    /** Status, approval and limit fields are set only via AgencyApprovalService/admin. */
    protected $fillable = [
        'name', 'slug', 'trade_license_number', 'business_email', 'business_phone',
        'address', 'district_id', 'website',
    ];

    protected function casts(): array
    {
        return [
            'status' => AgencyStatus::class,
            'approved_at' => 'datetime',
            'tier' => AgencyTier::class,
            'credit_limit' => MoneyCast::class.':credit_limit_minor,currency',
            'max_markup_percent' => 'decimal:2',
            'can_set_prices' => 'boolean',
            'is_authorized_dropoff' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /** Sub-users (Phase 2). The owner is owner(). */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AgencyDocument::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === AgencyStatus::Approved;
    }
}
