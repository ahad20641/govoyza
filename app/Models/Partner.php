<?php

namespace App\Models;

use App\Enums\KycStatus;
use App\Enums\PartnerDocumentType;
use App\Enums\PartnerStatus;
use App\Services\Partners\PartnerAgreement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A verified supplier. Never exposed outside admin and partner contexts
 * (CLAUDE.md rule 1: supplier invisibility).
 *
 * Status, KYC, agreement and approval fields are not mass-assignable: they
 * change only through PartnerOnboarding / PartnerApproval.
 */
class Partner extends Model
{
    use SoftDeletes;

    protected $fillable = ['legal_name', 'contact_phone'];

    protected $hidden = ['bank_details', 'agreement_ip'];

    protected function casts(): array
    {
        return [
            'status' => PartnerStatus::class,
            'kyc_status' => KycStatus::class,
            'bank_details' => 'encrypted:array',
            'agreement_signed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PartnerDocument::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isActive(): bool
    {
        return $this->status === PartnerStatus::Active;
    }

    public function hasBankDetails(): bool
    {
        return filled($this->bank_details['account_number'] ?? null);
    }

    /** Signed the agreement version currently in force. */
    public function hasSignedCurrentAgreement(): bool
    {
        return $this->agreement_signed_at !== null
            && $this->agreement_version === app(PartnerAgreement::class)->version();
    }

    /** @return list<PartnerDocumentType> required KYC documents not uploaded yet */
    public function missingDocuments(): array
    {
        $have = $this->documents()->pluck('document_type')->all(); // cast: PartnerDocumentType[]

        return array_values(array_filter(
            PartnerDocumentType::required(),
            fn (PartnerDocumentType $type) => ! in_array($type, $have, true),
        ));
    }

    /** @return list<string> what still blocks approval, in words */
    public function onboardingGaps(): array
    {
        $gaps = array_map(fn (PartnerDocumentType $t) => "Upload: {$t->label()}", $this->missingDocuments());

        if (! $this->hasBankDetails()) {
            $gaps[] = 'Add bank details';
        }

        if (! $this->hasSignedCurrentAgreement()) {
            $gaps[] = 'Sign the partner agreement';
        }

        return $gaps;
    }
}
