<?php

namespace App\Models;

use Database\Factories\SavedTravellerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Passport details a customer stores once and reuses on later orders. Customer portal.
 * Task 1.22 — Customer portal. Passport number and DOB are encrypted (CLAUDE.md rule 6).
 */
class SavedTraveller extends Model
{
    /** @use HasFactory<SavedTravellerFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'given_names', 'surname', 'passport_number', 'nationality_iso2', 'date_of_birth', 'passport_expiry',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
