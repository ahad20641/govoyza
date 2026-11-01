<?php

namespace App\Domain\Catalogue;

use App\Enums\OfferPriority;
use App\Enums\OfferRevisionStatus;
use App\Enums\ProductStatus;
use App\Enums\SupplierOfferStatus;
use App\Models\OfferRevision;
use App\Models\Product;
use App\Models\SupplierOffer;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OfferReviewWorkflow
{
    private const REVISION_FIELDS = [
        'cost_minor', 'currency', 'processing_days', 'capacity_per_week', 'inclusions', 'refund_terms',
    ];

    /** @return Collection<int, SupplierOffer> */
    public function comparableOffers(SupplierOffer $offer): Collection
    {
        $product = $offer->product;
        $matchingProducts = Product::query()
            ->where('country_id', $product->country_id)
            ->where('visa_type_id', $product->visa_type_id)
            ->when(
                $product->nationality_iso2 === null,
                fn ($query) => $query->whereNull('nationality_iso2'),
                fn ($query) => $query->where('nationality_iso2', $product->nationality_iso2),
            )
            ->select('id');

        return SupplierOffer::query()
            ->with(['partner', 'product.country', 'product.visaType'])
            ->whereIn('product_id', $matchingProducts)
            ->where(function ($query) use ($offer) {
                $query->whereIn('status', [SupplierOfferStatus::Live, SupplierOfferStatus::Pending])
                    ->orWhereKey($offer->getKey());
            })
            ->orderBy('cost_minor')
            ->get();
    }

    /** @param array<string, mixed> $catalogue */
    public function approveOffer(SupplierOffer $offer, User $reviewer, array $catalogue): SupplierOffer
    {
        $this->authorizeReview($reviewer, $offer);
        $catalogue = $this->validateCatalogueData($catalogue);

        return DB::transaction(function () use ($offer, $reviewer, $catalogue): SupplierOffer {
            $locked = SupplierOffer::query()->whereKey($offer->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== SupplierOfferStatus::Pending) {
                throw new DomainException('Only pending offers can be approved.');
            }

            $product = Product::query()->whereKey($locked->product_id)->lockForUpdate()->firstOrFail();
            $priority = OfferPriority::from($catalogue['priority']);

            $product->fill(Arr::only($catalogue, [
                'public_name', 'description', 'b2c_markup_type', 'b2c_markup_value', 'b2b_markup_type', 'b2b_markup_value',
            ]));
            $product->status = ProductStatus::Live;
            $product->save();

            if ($priority === OfferPriority::Primary) {
                SupplierOffer::query()
                    ->where('product_id', $product->id)
                    ->whereKeyNot($locked->getKey())
                    ->where('priority', OfferPriority::Primary)
                    ->where('status', SupplierOfferStatus::Live)
                    ->lockForUpdate()
                    ->get()
                    ->each(function (SupplierOffer $existing): void {
                        $existing->priority = OfferPriority::Backup;
                        $existing->save();
                    });
            }

            $locked->priority = $priority;
            $locked->status = SupplierOfferStatus::Live;
            $locked->reviewer_id = $reviewer->id;
            $locked->reviewed_at = now();
            $locked->review_comment = null;
            $locked->save();

            return $locked;
        });
    }

    public function requestOfferChanges(SupplierOffer $offer, User $reviewer, string $comment): SupplierOffer
    {
        return $this->decideOffer($offer, $reviewer, SupplierOfferStatus::ChangesRequested, $comment);
    }

    public function rejectOffer(SupplierOffer $offer, User $reviewer, string $comment): SupplierOffer
    {
        return $this->decideOffer($offer, $reviewer, SupplierOfferStatus::Rejected, $comment);
    }

    public function approveRevision(OfferRevision $revision, User $reviewer): OfferRevision
    {
        $offer = $revision->supplierOffer;
        $this->authorizeReview($reviewer, $offer);

        return DB::transaction(function () use ($revision, $reviewer): OfferRevision {
            $lockedRevision = OfferRevision::query()->whereKey($revision->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedRevision->status !== OfferRevisionStatus::Pending) {
                throw new DomainException('Only pending revisions can be approved.');
            }

            $offer = SupplierOffer::query()->whereKey($lockedRevision->supplier_offer_id)->lockForUpdate()->firstOrFail();

            if ($offer->status !== SupplierOfferStatus::Live) {
                throw new DomainException('Only revisions to live offers can be approved.');
            }

            $payload = Arr::only($lockedRevision->payload, self::REVISION_FIELDS);
            $lockedRevision->payload = [
                ...$lockedRevision->payload,
                'previous_cost_minor' => $offer->cost_minor,
            ];
            $offer->fill($payload);
            $offer->save();

            $lockedRevision->status = OfferRevisionStatus::Approved;
            $lockedRevision->reviewer_id = $reviewer->id;
            $lockedRevision->reviewed_at = now();
            $lockedRevision->save();

            return $lockedRevision;
        });
    }

    public function requestRevisionChanges(OfferRevision $revision, User $reviewer, string $comment): OfferRevision
    {
        return $this->decideRevision($revision, $reviewer, OfferRevisionStatus::ChangesRequested, $comment);
    }

    public function rejectRevision(OfferRevision $revision, User $reviewer, string $comment): OfferRevision
    {
        return $this->decideRevision($revision, $reviewer, OfferRevisionStatus::Rejected, $comment);
    }

    /** @param array<string, mixed> $catalogue
     * @return array<string, mixed>
     */
    private function validateCatalogueData(array $catalogue): array
    {
        return Validator::make($catalogue, [
            'public_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'b2c_markup_type' => ['required', 'in:fixed,percent'],
            'b2c_markup_value' => ['required', 'integer', 'min:0'],
            'b2b_markup_type' => ['required', 'in:fixed,percent'],
            'b2b_markup_value' => ['required', 'integer', 'min:0'],
            'priority' => ['required', 'in:primary,backup,other'],
        ])->validate();
    }

    private function decideOffer(
        SupplierOffer $offer,
        User $reviewer,
        SupplierOfferStatus $decision,
        string $comment,
    ): SupplierOffer {
        $this->authorizeReview($reviewer, $offer);
        $comment = $this->requiredComment($comment);

        return DB::transaction(function () use ($offer, $reviewer, $decision, $comment): SupplierOffer {
            $locked = SupplierOffer::query()->whereKey($offer->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== SupplierOfferStatus::Pending) {
                throw new DomainException('Only pending offers can be reviewed.');
            }

            $locked->status = $decision;
            $locked->reviewer_id = $reviewer->id;
            $locked->reviewed_at = now();
            $locked->review_comment = $comment;
            $locked->save();

            return $locked;
        });
    }

    private function decideRevision(
        OfferRevision $revision,
        User $reviewer,
        OfferRevisionStatus $decision,
        string $comment,
    ): OfferRevision {
        $this->authorizeReview($reviewer, $revision->supplierOffer);
        $comment = $this->requiredComment($comment);

        return DB::transaction(function () use ($revision, $reviewer, $decision, $comment): OfferRevision {
            $locked = OfferRevision::query()->whereKey($revision->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== OfferRevisionStatus::Pending) {
                throw new DomainException('Only pending revisions can be reviewed.');
            }

            $locked->status = $decision;
            $locked->reviewer_id = $reviewer->id;
            $locked->reviewed_at = now();
            $locked->comment = $comment;
            $locked->save();

            return $locked;
        });
    }

    private function authorizeReview(User $reviewer, SupplierOffer $offer): void
    {
        if (! $reviewer->can('review', $offer)) {
            throw new AuthorizationException('You cannot review supplier offers.');
        }
    }

    private function requiredComment(string $comment): string
    {
        $comment = trim($comment);

        if ($comment === '' || mb_strlen($comment) > 2000) {
            throw new DomainException('A review comment between 1 and 2000 characters is required.');
        }

        return $comment;
    }
}
