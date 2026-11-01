<?php

namespace App\Domain\Partners;

use App\Enums\KycStatus;
use App\Enums\PartnerStatus;
use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\Partner;
use App\Models\PartnerInvitation;
use App\Models\User;
use App\Notifications\PartnerInvited;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Partners join by invite link only (the portal is never linked from the public
 * site). The link token is random; only its sha256 is stored, it expires after
 * config('govoyza.partner.invitation_days') and works once.
 */
class PartnerInvitations
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function invite(string $email, ?string $legalName, User $inviter): PartnerInvitation
    {
        $token = Str::random(48);

        $invitation = new PartnerInvitation;
        $invitation->email = strtolower(trim($email));
        $invitation->legal_name = $legalName;
        $invitation->token_hash = hash('sha256', $token);
        $invitation->invited_by = $inviter->id;
        $invitation->expires_at = now()->addDays((int) config('govoyza.partner.invitation_days'));
        $invitation->save();

        Notification::route('mail', $invitation->email)
            ->notify(new PartnerInvited($this->url($token), $invitation->expires_at));

        $this->audit->log('partner.invited', $invitation, new: ['email' => $invitation->email], userId: $inviter->id);

        return $invitation;
    }

    public function url(string $token): string
    {
        return route('filament.partner.invitation', ['token' => $token]);
    }

    /** The usable invitation for a link token, or null. */
    public function find(string $token): ?PartnerInvitation
    {
        $invitation = PartnerInvitation::firstWhere('token_hash', hash('sha256', $token));

        return $invitation?->isUsable() ? $invitation : null;
    }

    /**
     * Creates the partner (pending) and its first user. The email is verified,
     * because only its owner received the link.
     *
     * @param  array{legal_name: string, name: string, phone?: ?string, password: string}  $data
     */
    public function accept(PartnerInvitation $invitation, array $data): User
    {
        return DB::transaction(function () use ($invitation, $data) {
            $invitation = PartnerInvitation::lockForUpdate()->findOrFail($invitation->id);
            abort_unless($invitation->isUsable(), 410, 'This invitation link is no longer valid.');

            $partner = new Partner(['legal_name' => $data['legal_name'], 'contact_phone' => $data['phone'] ?? null]);
            $partner->status = PartnerStatus::Pending;
            $partner->kyc_status = KycStatus::NotSubmitted;
            $partner->save();

            $user = new User([
                'name' => $data['name'],
                'email' => $invitation->email,
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
            ]);
            $user->assignRole(RoleSlug::Partner);
            $user->partner_id = $partner->id;
            $user->status = UserStatus::Active;
            $user->email_verified_at = now();
            $user->save();

            $invitation->accepted_at = now();
            $invitation->partner_id = $partner->id;
            $invitation->save();

            $this->audit->log('partner.joined', $partner, new: ['legal_name' => $partner->legal_name], userId: $user->id);

            return $user;
        });
    }
}
