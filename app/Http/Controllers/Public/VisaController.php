<?php

namespace App\Http\Controllers\Public;

use App\Domain\Pricing\PriceCalculator;
use App\Enums\Currency;
use App\Enums\OrderChannel;
use App\Enums\ProductStatus;
use App\Enums\SupplierOfferStatus;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Nationality;
use App\Models\Product;
use App\Models\VisaType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Visa finder results, country pages, visa-type pages and the service detail page. These pages are
 * generated from the catalogue and are the main SEO engine. Public site, govoyza.com.
 * Tasks 1.13 (finder), 1.14 (country/type/service pages, SEO, JSON-LD, sitemap).
 *
 * Only live Products are shown; supplier offers never load here (CLAUDE.md rule 1).
 */
class VisaController extends Controller
{
    /** Finder results, or every destination when no search was made. */
    public function index(Request $request, PriceCalculator $prices): View
    {
        $nationalities = Nationality::query()->active()->with('country:id,iso2')->get();
        $nationalityCodes = $nationalities->pluck('country.iso2')->filter()->values()->all();
        $filters = $request->validate([
            'nationality' => ['nullable', 'string', 'size:2', Rule::in($nationalityCodes)],
            'destination' => ['nullable', 'string', Rule::exists('countries', 'slug')->where('is_active', true)],
            'purpose' => ['nullable', 'string', Rule::exists('visa_types', 'slug')->where('is_active', true)],
        ]);

        $nationality = isset($filters['nationality']) ? strtoupper($filters['nationality']) : null;
        $nationalityLabel = $nationalities->first(fn (Nationality $item) => $item->country?->iso2 === $nationality)?->demonym;
        $destination = isset($filters['destination'])
            ? Country::query()->active()->where('slug', $filters['destination'])->firstOrFail()
            : null;
        $purpose = $filters['purpose'] ?? null;

        $products = Product::query()->live()
            ->whereHas('supplierOffers', fn ($query) => $query->where('status', SupplierOfferStatus::Live))
            ->with([
                'country',
                'visaType',
                'requirements',
                'supplierOffers' => fn ($query) => $query->where('status', SupplierOfferStatus::Live)
                    ->orderByRaw("CASE priority WHEN 'primary' THEN 0 WHEN 'backup' THEN 1 ELSE 2 END")
                    ->orderBy('id'),
            ])
            ->when($destination, fn ($q) => $q->where('country_id', $destination->id))
            ->when($purpose, fn ($q) => $q->whereHas('visaType', fn ($t) => $t->where('slug', $purpose)))
            ->when($nationality, fn ($q) => $q->where(fn ($matches) => $matches
                ->whereNull('nationality_iso2')->orWhere('nationality_iso2', $nationality)))
            ->orderBy('public_name')
            ->limit(50)
            ->get();

        $currency = Currency::from(config('govoyza.currency'));
        $productPrices = $products->mapWithKeys(fn (Product $product) => [
            $product->id => $prices->quote($product, OrderChannel::B2c, 'retail', $currency),
        ]);

        return view('public.visa.index', [
            'products' => $products,
            'productPrices' => $productPrices,
            'destination' => $destination,
            'nationality' => $nationality,
            'nationalityLabel' => $nationalityLabel,
            'purpose' => $purpose,
        ]);
    }

    public function country(Country $country): View
    {
        abort_unless($country->is_active, 404);

        return view('public.visa.country', [
            'country' => $country,
            'products' => $country->products()->live()->with('visaType')->orderBy('public_name')->get(),
        ]);
    }

    public function visaType(Country $country, VisaType $visaType): View
    {
        abort_unless($country->is_active && $visaType->is_active, 404);

        return view('public.visa.type', [
            'country' => $country,
            'visaType' => $visaType,
            'products' => $country->products()->live()->where('visa_type_id', $visaType->id)->get(),
        ]);
    }

    public function service(Product $product): View
    {
        abort_unless($product->status === ProductStatus::Live, 404);

        return view('public.visa.service', [
            'product' => $product->load(['country', 'visaType', 'requirements']),
        ]);
    }
}
