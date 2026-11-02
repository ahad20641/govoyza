<?php

namespace Tests\Feature;

use App\Enums\MarkupType;
use App\Enums\ProductStatus;
use App\Enums\SupplierOfferStatus;
use App\Models\Country;
use App\Models\Product;
use App\Models\SupplierOffer;
use App\Models\VisaType;
use Tests\TestCase;

/** Public visa finder filtering and price presentation. Public site, task 1.13. */
class VisaFinderTest extends TestCase
{
    public function test_home_page_has_a_working_finder_form_without_the_coming_soon_placeholder(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('I am a citizen of')
            ->assertSee('Going to')
            ->assertSee('For')
            ->assertSee('<option value="BD" selected>', false)
            ->assertDontSee('Coming in task 1.13');
    }

    public function test_finder_filters_by_nationality_destination_and_purpose_and_shows_price_timeline_and_checklist(): void
    {
        config(['govoyza.pricing.vat_rate' => '0']);
        $destination = Country::firstWhere('iso2', 'MY');
        $destination->update(['is_destination' => true]);
        $otherDestination = Country::firstWhere('iso2', 'SG');
        $otherDestination->update(['is_destination' => true]);
        $tourist = VisaType::firstWhere('slug', 'tourist');
        $medical = VisaType::firstWhere('slug', 'medical');

        $matching = $this->product($destination, $tourist, 'Malaysia tourist visa for Bangladeshi passport', 'BD');
        $matching->update(['b2c_markup_type' => MarkupType::Percent, 'b2c_markup_value' => 20]);
        $this->offer($matching, 'Secret Malaysia Supplier Ltd', 5000);
        $matching->requirements()->create([
            'label' => 'Passport bio page',
            'doc_type' => 'passport',
            'is_required' => true,
            'sort_order' => 0,
            'rules' => ['formats' => ['pdf'], 'max_mb' => 5],
        ]);

        $anyNationality = $this->product($destination, $tourist, 'Malaysia tourist visa for any passport', null);
        $this->offer($anyNationality, 'Secret Universal Supplier Ltd', 6000);
        $this->offer($this->product($destination, $tourist, 'Malaysia tourist visa for US passport', 'US'), 'US Supplier Ltd', 7000);
        $this->offer($this->product($otherDestination, $tourist, 'Singapore tourist visa for Bangladeshi passport', 'BD'), 'Singapore Supplier Ltd', 8000);
        $this->offer($this->product($destination, $medical, 'Malaysia medical visa for Bangladeshi passport', 'BD'), 'Medical Supplier Ltd', 9000);

        $this->get(route('visa.index', [
            'nationality' => 'BD',
            'destination' => $destination->slug,
            'purpose' => $tourist->slug,
        ]))
            ->assertOk()
            ->assertSee('Showing services for Bangladeshi passports.')
            ->assertSee('Malaysia tourist visa for Bangladeshi passport')
            ->assertSee('Malaysia tourist visa for any passport')
            ->assertSee('৳60.00')
            ->assertSee('5–12 days')
            ->assertSee('Passport bio page')
            ->assertDontSee('Malaysia tourist visa for US passport')
            ->assertDontSee('Singapore tourist visa for Bangladeshi passport')
            ->assertDontSee('Malaysia medical visa for Bangladeshi passport')
            ->assertDontSee('Secret Malaysia Supplier Ltd')
            ->assertDontSee('৳50.00');
    }

    public function test_finder_rejects_an_unknown_nationality_code(): void
    {
        $this->get(route('visa.index', ['nationality' => 'ZZ']))
            ->assertRedirect()
            ->assertSessionHasErrors('nationality');
    }

    private function product(Country $country, VisaType $visaType, string $name, ?string $nationality): Product
    {
        return Product::factory()->create([
            'country_id' => $country->id,
            'visa_type_id' => $visaType->id,
            'nationality_iso2' => $nationality,
            'public_name' => $name,
            'slug' => str()->slug($name).'-'.str()->random(8),
            'status' => ProductStatus::Live,
            'processing_days_min' => 5,
            'processing_days_max' => 12,
            'b2c_markup_type' => MarkupType::Fixed,
            'b2c_markup_value' => 0,
            'embassy_fee_minor' => 0,
            'currency' => 'BDT',
        ]);
    }

    private function offer(Product $product, string $partnerName, int $costMinor): SupplierOffer
    {
        $offer = SupplierOffer::factory()->for($product)->create([
            'cost_minor' => $costMinor,
            'currency' => 'BDT',
            'status' => SupplierOfferStatus::Live,
        ]);
        $offer->partner->update(['legal_name' => $partnerName]);

        return $offer;
    }
}
