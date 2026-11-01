<?php

namespace App\Models;

use Database\Factories\ApplicantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A traveller on an order. Customer and agent portals; partners see only the name (CLAUDE.md rule 2).
 * Task 1.17 — Order placement.
 *
 * Passport number and date of birth are encrypted at rest (CLAUDE.md rule 6).
 */
class Applicant extends Model
{
    /** @use HasFactory<ApplicantFactory> */
    use HasFactory;

    protected $fillable = [
        'saved_traveller_id', 'given_names', 'surname', 'passport_number',
        'nationality_iso2', 'date_of_birth', 'passport_expiry',
    ];

    protected $hidden = ['passport_number', 'date_of_birth'];

    protected function casts(): array
    {
        return [
            'passport_number' => 'encrypted',
            'date_of_birth' => 'encrypted',
            'passport_expiry' => 'date',
        ];
    }

    public function fullName(): string
    {
        return trim("{$this->given_names} {$this->surname}");
    }

    // ==================== Relationships ====================

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function savedTraveller(): BelongsTo
    {
        return $this->belongsTo(SavedTraveller::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
