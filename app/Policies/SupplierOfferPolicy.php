<?php

namespace App\Policies;

use App\Enums\OfferRevisionStatus;
use App\Enums\PartnerStatus;
use App\Enums\SupplierOfferStatus;
use App\Enums\UserType;
use App\Models\SupplierOffer;
use App\Models\User;

class SupplierOfferPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasActivePartner($user);
    }

    public function view(User $user, SupplierOffer $offer): bool
    {
        return $this->owns($user, $offer);
    }

    public function create(User $user): bool
    {
        return $this->hasActivePartner($user);
    }

    public function update(User $user, SupplierOffer $offer): bool
    {
        if (! $this->owns($user, $offer)) {
            return false;
        }

        if (in_array($offer->status, [SupplierOfferStatus::Draft, SupplierOfferStatus::Rejected], true)) {
            return true;
        }

        return $offer->status === SupplierOfferStatus::Live
            && ! $offer->revisions()->where('status', OfferRevisionStatus::Pending)->exists();
    }

    public function delete(User $user, SupplierOffer $offer): bool
    {
        return false;
    }

    private function owns(User $user, SupplierOffer $offer): bool
    {
        return $this->hasActivePartner($user)
            && (int) $user->partner_id === (int) $offer->partner_id;
    }

    private function hasActivePartner(User $user): bool
    {
        return $user->type === UserType::PartnerUser
            && $user->partner?->status === PartnerStatus::Active;
    }
}
