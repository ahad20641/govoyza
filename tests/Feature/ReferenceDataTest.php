<?php

namespace Tests\Feature;

use App\Enums\Continent;
use App\Enums\CountryRegion;
use App\Enums\RoleSlug;
use App\Filament\Resources\Countries\Pages\EditCountry;
use App\Filament\Resources\VisaTypes\Pages\EditVisaType;
use App\Models\Country;
use App\Models\Nationality;
use App\Models\User;
use App\Models\VisaFormat;
use App\Models\VisaType;
use Database\Seeders\ReferenceDataSeeder;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

/** TestCase seeds DatabaseSeeder (once per run), which includes ReferenceDataSeeder. */
class ReferenceDataTest extends TestCase
{
    private function staff(RoleSlug $role): User
    {
        $user = User::factory()->role($role)->create();
        $user->saveAppAuthenticationSecret(AppAuthentication::make()->generateSecret());

        return $user;
    }

    public function test_all_countries_and_passport_nationalities_are_seeded(): void
    {
        $this->assertSame(250, Country::count()); // ISO 3166-1 (249) + Kosovo
        $this->assertSame(199, Nationality::count()); // 193 UN members + VA, PS, TW, HK, MO, XK

        $bd = Country::firstWhere('iso2', 'BD');
        $this->assertSame('Bangladesh', $bd->name);
        $this->assertSame('BGD', $bd->iso3);
        $this->assertSame('bangladesh', $bd->slug);
        $this->assertSame(Continent::Asia, $bd->continent);
        $this->assertSame('🇧🇩', $bd->flag);
        $this->assertSame('Bangladeshi', $bd->nationality->demonym);

        // The home country's passport comes first in forms.
        $this->assertSame('BD', Nationality::orderBy('sort_order')->orderBy('demonym')->first()->country->iso2);

        // Territories travelling on another passport get no nationality.
        $this->assertNull(Country::firstWhere('iso2', 'PR')->nationality);

        $this->assertSame(Country::count(), Country::distinct()->count('slug'));
    }

    public function test_schengen_and_gcc_regions(): void
    {
        $schengen = Country::where('region', CountryRegion::Schengen)->pluck('iso2')->sort()->values()->all();

        $this->assertCount(29, $schengen);
        $this->assertContains('BG', $schengen);
        $this->assertContains('RO', $schengen);
        $this->assertContains('CH', $schengen);
        foreach (['CY', 'IE', 'GB'] as $outside) {
            $this->assertNotContains($outside, $schengen);
        }

        $this->assertSame(6, Country::where('region', CountryRegion::Gcc)->count());
        $this->assertSame(CountryRegion::Gcc, Country::firstWhere('iso2', 'AE')->region);
    }

    public function test_the_seven_visa_types_and_two_formats(): void
    {
        $this->assertSame(
            ['tourist', 'medical', 'business', 'student', 'work', 'transit', 'family'],
            VisaType::active()->pluck('slug')->all(),
        );
        $this->assertTrue(VisaFormat::firstWhere('slug', 'sticker')->requires_physical_passport);
        $this->assertFalse(VisaFormat::firstWhere('slug', 'evisa')->requires_physical_passport);
    }

    public function test_re_seeding_is_idempotent_and_keeps_admin_edits(): void
    {
        $thailand = Country::firstWhere('iso2', 'TH');
        $thailand->update(['is_destination' => true, 'is_popular' => true, 'description' => 'Our text', 'seo_title' => 'Thai visa']);
        $tourist = VisaType::firstWhere('slug', 'tourist');
        $tourist->update(['name' => 'Tourist / holiday']);
        Nationality::whereHas('country', fn ($q) => $q->where('iso2', 'IN'))->update(['is_active' => false]);

        $this->seed(ReferenceDataSeeder::class);
        $this->seed(ReferenceDataSeeder::class);

        $this->assertSame(250, Country::count());
        $this->assertSame(199, Nationality::count());
        $this->assertSame(7, VisaType::count());

        $thailand->refresh();
        $this->assertTrue($thailand->is_destination);
        $this->assertTrue($thailand->is_popular);
        $this->assertSame('Our text', $thailand->description);
        $this->assertSame('Thai visa', $thailand->seo_title);
        $this->assertSame('Tourist / holiday', $tourist->fresh()->name);
        $this->assertFalse(Country::firstWhere('iso2', 'IN')->nationality->is_active);
    }

    public function test_seeder_corrects_names_but_never_changes_a_slug(): void
    {
        Country::where('iso2', 'TR')->update(['name' => 'Turkey', 'slug' => 'turkey', 'region' => null]);

        $this->seed(ReferenceDataSeeder::class);

        $turkey = Country::firstWhere('iso2', 'TR');
        $this->assertSame('Türkiye', $turkey->name);
        $this->assertSame('turkey', $turkey->slug); // existing URLs keep working
    }

    public function test_operations_edit_countries_and_visa_types_and_finance_cannot(): void
    {
        $this->actingAs($this->staff(RoleSlug::Operations));
        Filament::setCurrentPanel('admin');

        $country = Country::firstWhere('iso2', 'MY');
        Livewire::test(EditCountry::class, ['record' => $country->getRouteKey()])
            ->fillForm(['is_destination' => true, 'description' => 'Malaysia e-visa for Bangladeshi passports.', 'seo_title' => 'Malaysia visa'])
            ->call('save')
            ->assertHasNoFormErrors();
        $country->refresh();
        $this->assertTrue($country->is_destination);
        $this->assertSame('Malaysia visa', $country->seo_title);
        $this->assertSame('Malaysia', $country->name); // name isn't a form field

        $type = VisaType::firstWhere('slug', 'medical');
        Livewire::test(EditVisaType::class, ['record' => $type->getRouteKey()])
            ->fillForm(['name' => 'Medical treatment'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('Medical treatment', $type->fresh()->name);
        $this->assertSame('medical', $type->fresh()->slug);

        $admin = 'http://'.config('govoyza.domains.admin');
        $this->actingAs($this->staff(RoleSlug::Finance))->get($admin.'/countries')->assertForbidden();
        $this->actingAs($this->staff(RoleSlug::Operations))->get($admin.'/countries')->assertOk();
        $this->actingAs($this->staff(RoleSlug::Operations))->get($admin.'/visa-types')->assertOk();
    }

    public function test_reference_data_is_public_to_read(): void
    {
        $this->assertTrue(Gate::forUser(null)->allows('viewAny', VisaType::class));
        $this->assertFalse(User::factory()->create()->can('update', VisaType::first()));
    }
}
