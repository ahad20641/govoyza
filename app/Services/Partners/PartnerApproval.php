<?php

namespace App\Services\Partners;

use App\Enums\KycStatus;
use App\Enums\PartnerStatus;
use App\Models\Partner;
use App\Models\User;
use App\Notifications\PartnerActivated;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use LogicException;

/** Admin decisions on a partner: Pending → Active, Active ↔ Suspended. */
class PartnerApproval
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** @throws LogicException when onboarding isn't complete */
    public function approve(Partner $partner, User $admin): void
    {
        if ($gaps = $partner->onboardingGaps()) {
            throw new LogicException('Onboarding is not complete: '.implode('; ', $gaps).'.');
        }

        DB::transaction(function () use ($partner, $admin) {
            $partner->status = PartnerStatus::Active;
            $partner->kyc_status = KycStatus::Approved;
            $partner->approved_at = now();
            $partner->approved_by = $admin->id;
            $partner->status_reason = null;
            $partner->save();

            $this->audit->log('partner.approved', $partner, userId: $admin->id);
        });

        Notification::send($partner->users, new PartnerActivated);
    }

    public function suspend(Partner $partner, User $admin, string $reason): void
    {
        $partner->status = PartnerStatus::Suspended;
        $partner->status_reason = $reason;
        $partner->save();

        $this->audit->log('partner.suspended', $partner, new: ['reason' => $reason], userId: $admin->id);
    }

    /** Back to Active if it had been approved before, otherwise back to Pending. */
    public function reactivate(Partner $partner, User $admin): void
    {
        $partner->status = $partner->approved_at ? PartnerStatus::Active : PartnerStatus::Pending;
        $partner->status_reason = null;
        $partner->save();

        $this->audit->log('partner.reactivated', $partner, userId: $admin->id);
    }
}
