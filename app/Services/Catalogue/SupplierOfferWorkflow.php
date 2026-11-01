<?php

namespace App\Services\Catalogue;

use App\Enums\OfferRevisionStatus;
use App\Enums\PartnerStatus;
use App\Enums\SupplierOfferStatus;
use App\Models\OfferRevision;
use App\Models\Partner;
use App\Models\SupplierOffer;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SupplierOfferWorkflow
{
    private const PARTNER_FIELDS = [
        'product_id', 'cost_minor', 'currency', 'processing_days', 'capacity_per_week', 'inclusions', 'refund_terms',
    ];

    private const REVISION_FIELDS = [
        'cost_minor', 'currency', 'processing_days', 'capacity_per_week', 'inclusions', 'refund_terms',
    ];

    /** @param array<string, mixed> $attributes */
    public function createDraft(Partner $partner, array $attributes): SupplierOffer
    {
        $this->assertActivePartner($partner);

        return $partner->supplierOffers()->create([
            ...Arr::only($attributes, self::PARTNER_FIELDS),
            'status' => SupplierOfferStatus::Draft,
        ]);
    }

    public function submitForReview(Partner $partner, SupplierOffer $offer): SupplierOffer
    {
        $this->assertOwnership($partner, $offer);

        return DB::transaction(function () use ($partner, $offer): SupplierOffer {
            $locked = $partner->supplierOffers()->whereKey($offer->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [SupplierOfferStatus::Draft, SupplierOfferStatus::Rejected], true)) {
                throw new DomainException('Only draft or rejected offers can be submitted for review.');
            }

            $locked->status = SupplierOfferStatus::Pending;
            $locked->save();

            return $locked;
        });
    }

    /** @param array<string, mixed> $attributes */
    public function updateOffer(Partner $partner, SupplierOffer $offer, array $attributes): SupplierOffer|OfferRevision
    {
        $this->assertOwnership($partner, $offer);

        return DB::transaction(function () use ($partner, $offer, $attributes): SupplierOffer|OfferRevision {
            $locked = $partner->supplierOffers()->whereKey($offer->getKey())->lockForUpdate()->firstOrFail();
            $changes = Arr::only($attributes, self::REVISION_FIELDS);

            if (in_array($locked->status, [SupplierOfferStatus::Draft, SupplierOfferStatus::Rejected], true)) {
                $locked->fill($changes);
                $locked->save();

                return $locked;
            }

            if ($locked->status !== SupplierOfferStatus::Live) {
                throw new DomainException('This offer cannot be edited in its current state.');
            }

            if ($locked->revisions()->where('status', OfferRevisionStatus::Pending)->exists()) {
                throw new DomainException('An offer revision is already awaiting review.');
            }

            return $locked->revisions()->create([
                'payload' => $changes,
                'status' => OfferRevisionStatus::Pending,
                'submitted_at' => now(),
            ]);
        });
    }

    private function assertActivePartner(Partner $partner): void
    {
        if ($partner->status !== PartnerStatus::Active) {
            throw new AuthorizationException('Only active partners may manage supplier offers.');
        }
    }

    private function assertOwnership(Partner $partner, SupplierOffer $offer): void
    {
        $this->assertActivePartner($partner);

        if ((int) $offer->partner_id !== (int) $partner->getKey()) {
            throw new AuthorizationException('This supplier offer belongs to another partner.');
        }
    }
}
