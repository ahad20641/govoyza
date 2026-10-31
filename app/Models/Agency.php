<?php

namespace App\Models;

use App\Enums\AgencyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agency extends Model
{
    use SoftDeletes;

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
            'credit_limit' => 'decimal:2',
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
