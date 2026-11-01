<?php

namespace App\Models;

use App\Enums\OtpPurpose;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Written only by App\Services\Auth\OneTimePasswords. */
class OneTimePassword extends Model
{
    protected $guarded = ['*'];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
