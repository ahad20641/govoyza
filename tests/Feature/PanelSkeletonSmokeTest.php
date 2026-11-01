<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Filament\Pages\Payables;
use App\Filament\Pages\ProfitAndLoss;
use App\Filament\Pages\SystemSettings;
use App\Filament\Pages\TodayBoard;
use App\Filament\Partner\Pages\Earnings;
use App\Filament\Partner\Pages\Performance;
use App\Filament\Partner\Resources\ServiceJobs\ServiceJobResource;
use App\Filament\Resources\Agencies\AgencyResource;
use App\Filament\Resources\CmsPages\CmsPageResource;
use App\Filament\Resources\Coupons\CouponResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Payouts\PayoutResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Staff\StaffResource;
use App\Filament\Resources\SupplierOffers\SupplierOfferResource;
use App\Models\Applicant;
use App\Models\Order;
use App\Models\Partner;
use App\Models\ServiceJob;
use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Tests\TestCase;

/**
 * Sprint S1 smoke tests for the Filament panels: each new admin page opens for the staff role that holds its
 * permission and is refused (403) for one that doesn't; partner job pages are scoped to the partner.
 */
class PanelSkeletonSmokeTest extends TestCase
{
    // ==================== Helpers ====================

    private function staff(RoleSlug $role): User
    {
        $user = User::factory()->role($role)->create();
        $user->saveAppAuthenticationSecret(AppAuthentication::make()->generateSecret());

        return $user;
    }

    private function partnerUser(Partner $partner): User
    {
        $user = User::factory()->partner()->create();
        $user->partner_id = $partner->id;
        $user->save();
        $user->saveAppAuthenticationSecret(AppAuthentication::make()->generateSecret());

        return $user;
    }

    // ==================== Admin console ====================

    public function test_admin_pages_open_for_the_right_staff_role_only(): void
    {
        Filament::setCurrentPanel('admin');

        // page/resource => [role allowed, role refused]
        $matrix = [
            [TodayBoard::getUrl(), RoleSlug::Operations, null],
            [SupplierOfferResource::getUrl(), RoleSlug::Operations, RoleSlug::Finance],
            [OrderResource::getUrl(), RoleSlug::Finance, null],
            [CustomerResource::getUrl(), RoleSlug::Operations, RoleSlug::Finance],
            [AgencyResource::getUrl(), RoleSlug::Operations, RoleSlug::Finance],
            [StaffResource::getUrl(), RoleSlug::Owner, RoleSlug::Operations],
            [JournalEntryResource::getUrl(), RoleSlug::Finance, RoleSlug::Operations],
            [PayoutResource::getUrl(), RoleSlug::Finance, RoleSlug::Operations],
            [ProfitAndLoss::getUrl(), RoleSlug::Finance, RoleSlug::Operations],
            [Payables::getUrl(), RoleSlug::Finance, RoleSlug::Operations],
            [CmsPageResource::getUrl(), RoleSlug::Operations, RoleSlug::Finance],
            [PostResource::getUrl(), RoleSlug::Operations, RoleSlug::Finance],
            [CouponResource::getUrl(), RoleSlug::Owner, RoleSlug::Operations],
            [SystemSettings::getUrl(), RoleSlug::Owner, RoleSlug::Operations],
        ];

        Order::factory()->create();
        $customer = User::factory()->create();

        foreach ($matrix as [$url, $allowed, $refused]) {
            $this->actingAs($this->staff($allowed))->get($url)->assertOk();
            $this->actingAs($this->staff(RoleSlug::Owner))->get($url)->assertOk();

            if ($refused !== null) {
                $this->actingAs($this->staff($refused))->get($url)->assertForbidden();
            }

            $this->actingAs($customer)->get($url)->assertForbidden();
        }
    }

    // ==================== Partner portal ====================

    public function test_partner_job_pages_are_scoped_to_the_partner(): void
    {
        Filament::setCurrentPanel('partner');

        $job = ServiceJob::factory()->create();
        Applicant::factory()->for($job->order)->create(['given_names' => 'Rahim', 'surname' => 'Uddin']);
        $mine = $this->partnerUser($job->partner);
        $other = $this->partnerUser(Partner::factory()->create());

        $this->actingAs($mine);
        foreach ([ServiceJobResource::getUrl(), Earnings::getUrl(), Performance::getUrl()] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(ServiceJobResource::getUrl('view', ['record' => $job]))
            ->assertOk()
            ->assertSee($job->order->number)
            ->assertSee('Rahim Uddin')
            ->assertDontSee($job->order->user->email);  // customer contact never reaches partners

        // Another partner's job doesn't exist as far as this partner can tell (scoped query → 404).
        $this->actingAs($other)->get(ServiceJobResource::getUrl('view', ['record' => $job]))->assertNotFound();

        $pending = $this->partnerUser(Partner::factory()->pending()->create());
        foreach ([ServiceJobResource::getUrl(), Earnings::getUrl(), Performance::getUrl()] as $url) {
            $this->actingAs($pending)->get($url)->assertForbidden();
        }
    }
}
