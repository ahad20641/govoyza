<?php

namespace App\Domain\Partners;

use App\Enums\KycStatus;
use App\Enums\PartnerDocumentType;
use App\Models\Partner;
use App\Models\PartnerDocument;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** What a partner does in the portal before approval: KYC files, bank details, agreement. */
class PartnerOnboarding
{
    public const DISK = 'private';

    public function __construct(
        private readonly PartnerAgreement $agreement,
        private readonly AuditLogger $audit,
    ) {}

    /** Records a file already stored on the private disk (by the upload field). */
    public function addDocument(Partner $partner, PartnerDocumentType $type, string $path, ?string $originalName, User $by): PartnerDocument
    {
        $disk = Storage::disk(self::DISK);

        $stream = $disk->readStream($path);
        $context = hash_init('sha256');
        hash_update_stream($context, $stream);
        fclose($stream);

        $document = new PartnerDocument;
        $document->partner_id = $partner->id;
        $document->document_type = $type;
        $document->disk = self::DISK;
        $document->path = $path;
        $document->original_name = Str::limit($originalName ?: basename($path), 200, '');
        $document->mime_type = $disk->mimeType($path) ?: 'application/octet-stream';
        $document->size = $disk->size($path);
        $document->sha256 = hash_final($context);
        $document->uploaded_by = $by->id;
        $document->save();

        if (in_array($partner->kyc_status, [KycStatus::NotSubmitted, KycStatus::Rejected], true) && $partner->missingDocuments() === []) {
            $partner->kyc_status = KycStatus::Submitted;
            $partner->save();
        }

        return $document;
    }

    /** @param  array{account_name: string, bank_name: string, branch: ?string, account_number: string, routing_number: ?string}  $details */
    public function saveBankDetails(Partner $partner, array $details): void
    {
        $partner->bank_details = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $details);
        $partner->save();
    }

    /** Click-to-sign: typed full name, time, IP, agreement version and a hash of the exact text. */
    public function signAgreement(Partner $partner, User $user, string $fullName, ?string $ip): void
    {
        $partner->agreement_version = $this->agreement->version();
        $partner->agreement_text_sha256 = $this->agreement->sha256();
        $partner->agreement_signed_name = trim($fullName);
        $partner->agreement_signed_at = now();
        $partner->agreement_ip = $ip;
        $partner->agreement_signed_by = $user->id;
        $partner->save();

        $this->audit->log('partner.agreement_signed', $partner, new: [
            'version' => $partner->agreement_version,
            'signed_name' => $partner->agreement_signed_name,
        ], userId: $user->id);
    }
}
