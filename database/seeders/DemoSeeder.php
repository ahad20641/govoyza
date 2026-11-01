<?php

namespace Database\Seeders;

use App\Enums\AgencyStatus;
use App\Enums\AgencyTier;
use App\Enums\KycStatus;
use App\Enums\MarkupType;
use App\Enums\OfferPriority;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PartnerStatus;
use App\Enums\ProductStatus;
use App\Enums\RoleSlug;
use App\Enums\ServiceJobStatus;
use App\Enums\SupplierOfferStatus;
use App\Enums\UserStatus;
use App\Models\Agency;
use App\Models\Country;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Requirement;
use App\Models\SavedTraveller;
use App\Models\ServiceJob;
use App\Models\SupplierOffer;
use App\Models\User;
use App\Models\VisaType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sample data so every S1 page has something to show: a few live products with checklists, a demo partner
 * with offers and a job, a demo customer and agency with orders. Local and staging only: it does nothing in
 * any other environment. Idempotent (runs on every staging deploy) and never resets a password once set.
 * Sprint S1 — Skeleton.
 *
 * Demo logins get a random password. Set one with: bash deploy/password.sh reset-password EMAIL
 */
class DemoSeeder extends Seeder
{
    public const CUSTOMER_EMAIL = 'customer@demo.govoyza.test';

    public const AGENT_EMAIL = 'agent@demo.govoyza.test';

    public const PARTNER_EMAIL = 'partner@demo.govoyza.test';

    /** iso2 => [visa type slug, embassy fee in BDT minor units, supplier cost in BDT minor units] */
    private const PRODUCTS = [
        'TH' => ['tourist', 450000, 650000],
        'MY' => ['tourist', 0, 300000],
        'AE' => ['tourist', 1200000, 900000],
        'SG' => ['business', 350000, 700000],
        'FR' => ['tourist', 1100000, 1500000],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'staging'])) {
            $this->command?->warn('DemoSeeder skipped: only runs in local and staging.');

            return;
        }

        DB::transaction(function (): void {
            $partner = $this->partner();
            $products = $this->products($partner);
            $customer = $this->user(self::CUSTOMER_EMAIL, 'Demo Customer', RoleSlug::Client);
            $agency = $this->agency();

            $this->customerOrders($customer, $products, $partner);
            $this->agencyOrders($agency, $products);
        });
    }

    // ==================== People ====================

    private function user(string $email, string $name, RoleSlug $role): User
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $user = new User(['name' => $name, 'email' => $email, 'password' => Str::random(40)]);
            $user->assignRole($role);
            $user->forceFill(['status' => UserStatus::Active, 'email_verified_at' => now(), 'is_demo' => true])->save();
        }

        return $user;
    }

    private function partner(): Partner
    {
        // Fictional supplier; it only ever appears in the admin console and the partner portal.
        $partner = Partner::query()->firstOrNew(['legal_name' => 'Demo Visa Supplier (staging)']);
        $partner->forceFill([
            'status' => PartnerStatus::Active,
            'kyc_status' => KycStatus::Approved,
            'approved_at' => $partner->approved_at ?? now(),
            'sla_score' => 92,
        ])->save();

        $user = $this->user(self::PARTNER_EMAIL, 'Demo Partner', RoleSlug::Partner);
        $user->forceFill(['partner_id' => $partner->id])->save();

        return $partner;
    }

    private function agency(): Agency
    {
        $owner = $this->user(self::AGENT_EMAIL, 'Demo Agent', RoleSlug::Agency);

        $agency = Agency::query()->firstOrNew(['user_id' => $owner->id]);
        $agency->forceFill([
            'name' => 'Demo Travels (staging)',
            'slug' => 'demo-travels',
            'trade_license_number' => 'DEMO/0001',
            'business_email' => self::AGENT_EMAIL,
            'business_phone' => '+8801000000000',
            'address' => 'Demo address, Dhaka',
            'status' => AgencyStatus::Approved,
            'approved_at' => $agency->approved_at ?? now(),
            'tier' => AgencyTier::Silver,
            'currency' => 'BDT',
            'is_demo' => true,
        ])->save();

        return $agency;
    }

    // ==================== Catalogue ====================

    /** @return list<Product> */
    private function products(Partner $partner): array
    {
        $products = [];

        foreach (self::PRODUCTS as $iso2 => [$typeSlug, $embassyFee, $cost]) {
            $country = Country::query()->where('iso2', $iso2)->firstOrFail();
            $type = VisaType::query()->where('slug', $typeSlug)->firstOrFail();

            $product = Product::query()->firstOrNew(['slug' => "{$country->slug}-{$type->slug}-visa"]);
            $product->forceFill([
                'country_id' => $country->id,
                'visa_type_id' => $type->id,
                'public_name' => "{$country->name} {$type->name} Visa",
                'description' => "We check your documents, fill in the forms and submit your {$country->name} {$type->name} visa application for you.",
                'processing_days_min' => 5,
                'processing_days_max' => 12,
                'validity' => 'Up to 30 days stay',
                'b2c_markup_type' => MarkupType::Percent,
                'b2c_markup_value' => 20,
                'b2b_markup_type' => MarkupType::Percent,
                'b2b_markup_value' => 10,
                'embassy_fee_minor' => $embassyFee,
                'currency' => 'BDT',
                'status' => ProductStatus::Live,
            ])->save();

            foreach ([['Passport (valid 6+ months)', 'passport'], ['Recent photo, white background', 'photo'], ['Bank statement, last 6 months', 'bank_statement']] as $i => [$label, $docType]) {
                Requirement::query()->firstOrCreate(
                    ['product_id' => $product->id, 'doc_type' => $docType],
                    ['label' => $label, 'is_required' => true, 'sort_order' => $i, 'rules' => ['formats' => ['pdf', 'jpg', 'png'], 'max_mb' => 5]],
                );
            }

            SupplierOffer::query()->firstOrNew(['product_id' => $product->id, 'partner_id' => $partner->id])->forceFill([
                'cost_minor' => $cost,
                'currency' => 'BDT',
                'processing_days' => 10,
                'capacity_per_week' => 25,
                'priority' => OfferPriority::Primary,
                'status' => SupplierOfferStatus::Live,
                'inclusions' => ['Document check', 'Form filling', 'Submission'],
            ])->save();

            $products[] = $product;
        }

        return $products;
    }

    // ==================== Orders ====================

    /** @param list<Product> $products */
    private function customerOrders(User $customer, array $products, Partner $partner): void
    {
        SavedTraveller::query()->firstOrCreate(
            ['user_id' => $customer->id, 'given_names' => 'Ayesha', 'surname' => 'Rahman'],
            ['passport_number' => 'DEMO12345', 'nationality_iso2' => 'BD', 'date_of_birth' => '1992-04-18', 'passport_expiry' => now()->addYears(4)->toDateString()],
        );

        $statuses = [OrderStatus::DocumentsPending, OrderStatus::Assigned, OrderStatus::Completed];

        foreach ($statuses as $i => $status) {
            $order = $this->order('GV-DEMO-C'.($i + 1), $customer, null, $products[$i], $status);

            if ($status === OrderStatus::Assigned && ! $order->jobs()->exists()) {
                $offer = SupplierOffer::query()->where('product_id', $order->product_id)->where('partner_id', $partner->id)->firstOrFail();
                $job = new ServiceJob(['sla_due_at' => now()->addDays(10)]);
                $job->forceFill([
                    'order_id' => $order->id,
                    'supplier_offer_id' => $offer->id,
                    'partner_id' => $partner->id,
                    'status' => ServiceJobStatus::Assigned,
                    'assigned_at' => now(),
                    'cost_snapshot_minor' => $offer->cost_minor,
                    'currency' => $offer->currency,
                ])->save();
            }
        }
    }

    /** @param list<Product> $products */
    private function agencyOrders(Agency $agency, array $products): void
    {
        $this->order('GV-DEMO-A1', $agency->owner, $agency, $products[3], OrderStatus::UnderReview);
        $this->order('GV-DEMO-A2', $agency->owner, $agency, $products[4], OrderStatus::Paid);
    }

    /**
     * Demo orders set status and total directly; real orders go through OrderStateMachine and the
     * pricing engine (tasks 1.17, 1.18). The total here is a stand-in until PriceCalculator exists (1.12).
     */
    private function order(string $number, User $buyer, ?Agency $agency, Product $product, OrderStatus $status): Order
    {
        $order = Order::query()->firstOrNew(['number' => $number]);

        if (! $order->exists) {
            $order->forceFill([
                'user_id' => $buyer->id,
                'agency_id' => $agency?->id,
                'product_id' => $product->id,
                'channel' => $agency ? OrderChannel::B2b : OrderChannel::B2c,
                'tier' => $agency ? $agency->tier->value : 'retail',
                'status' => $status,
                'total_minor' => $product->embassy_fee_minor + 1_000_000,
                'currency' => 'BDT',
                'travel_date' => now()->addMonths(2)->toDateString(),
                'placed_at' => now()->subDays(3),
            ])->save();

            $order->applicants()->create([
                'given_names' => $agency ? 'Karim' : 'Ayesha',
                'surname' => $agency ? 'Hossain' : 'Rahman',
                'passport_number' => 'DEMO'.random_int(10000, 99999),
                'nationality_iso2' => 'BD',
                'date_of_birth' => '1990-01-01',
                'passport_expiry' => now()->addYears(4)->toDateString(),
            ]);
        }

        return $order;
    }
}
