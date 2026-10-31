<?php

namespace App\Models;

use App\Support\Hashing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    protected $fillable = ['nationality_id', 'date_of_birth', 'address', 'district_id'];

    protected $hidden = ['passport_number', 'passport_number_hash'];

    protected function casts(): array
    {
        return [
            'passport_number' => 'encrypted',
            'date_of_birth' => 'date',
            'is_demo' => 'boolean',
        ];
    }

    public function setPassportNumber(?string $number): void
    {
        $this->passport_number = $number;
        $this->passport_number_hash = $number ? Hashing::passport($number) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
