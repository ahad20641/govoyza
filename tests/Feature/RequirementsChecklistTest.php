<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\RoleSlug;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Models\Country;
use App\Models\Product;
use App\Models\Requirement;
use App\Models\User;
use App\Models\VisaType;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

/** Product requirement authoring and public checklist tests. Catalogue, task 1.11. */
class RequirementsChecklistTest extends TestCase
{
    public function test_operations_can_create_a_product_with_structured_document_requirements(): void
    {
        $country = Country::query()->firstOrFail();
        $country->update(['is_destination' => true]);
        $visaType = VisaType::query()->active()->firstOrFail();
        $this->actingAs(User::factory()->role(RoleSlug::Operations)->create());
        Filament::setCurrentPanel('admin');

        Livewire::test(CreateProduct::class)
            ->set('data.country_id', $country->id)
            ->set('data.visa_type_id', $visaType->id)
            ->set('data.public_name', 'Tourist visa checklist test')
            ->set('data.slug', 'tourist-visa-checklist-test')
            ->set('data.requirements', [[
                'label' => 'Passport bio page',
                'doc_type' => 'passport',
                'is_required' => true,
                'rules' => [
                    'formats' => ['pdf', 'jpg'],
                    'max_mb' => 5,
                    'passport_valid_months' => 6,
                ],
            ]])
            ->call('create')
            ->assertHasNoFormErrors();

        $requirement = Requirement::query()->sole();
        $this->assertSame('Passport bio page', $requirement->label);
        $this->assertSame('passport', $requirement->doc_type);
        $this->assertTrue($requirement->is_required);
        // JSON object keys may be reordered by the database driver.
        $this->assertSame(['pdf', 'jpg'], $requirement->rules['formats']);
        $this->assertSame(5, $requirement->rules['max_mb']);
        $this->assertSame(6, $requirement->rules['passport_valid_months']);
        $this->assertSame(Product::sole()->id, $requirement->product_id);
    }

    public function test_live_product_service_page_displays_required_and_optional_document_rules(): void
    {
        $country = Country::query()->firstOrFail();
        $visaType = VisaType::query()->active()->firstOrFail();
        $product = Product::create([
            'country_id' => $country->id,
            'visa_type_id' => $visaType->id,
            'public_name' => 'Tourist visa documents test',
            'slug' => 'tourist-visa-documents-test',
            'status' => ProductStatus::Live,
        ]);
        $product->requirements()->createMany([
            [
                'label' => 'Passport bio page',
                'doc_type' => 'passport',
                'is_required' => true,
                'sort_order' => 0,
                'rules' => ['formats' => ['pdf', 'jpg'], 'max_mb' => 5, 'passport_valid_months' => 6],
            ],
            [
                'label' => 'Travel itinerary',
                'doc_type' => 'itinerary',
                'is_required' => false,
                'sort_order' => 1,
                'rules' => ['formats' => ['pdf'], 'max_mb' => 10],
            ],
        ]);

        $this->get(route('visa.service', $product))
            ->assertOk()
            ->assertSee('Passport bio page')
            ->assertSee('Travel itinerary')
            ->assertSee('(optional)')
            ->assertSee('PDF, JPG')
            ->assertSee('5 MB')
            ->assertSee('6 months')
            ->assertDontSee('coming soon', false);
    }
}
