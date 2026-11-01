<?php

namespace App\Enums;

/** KYC documents a partner uploads. Required ones must be present before approval. */
enum PartnerDocumentType: string
{
    case TradeLicence = 'trade_licence';
    case OwnerId = 'owner_id';               // NID or passport of the owner/director
    case TaxCertificate = 'tax_certificate'; // e-TIN / BIN
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::TradeLicence => 'Trade licence',
            self::OwnerId => 'Owner / director ID (NID or passport)',
            self::TaxCertificate => 'Tax certificate (e-TIN / BIN)',
            self::Other => 'Other supporting document',
        };
    }

    /** @return list<self> */
    public static function required(): array
    {
        return [self::TradeLicence, self::OwnerId];
    }
}
