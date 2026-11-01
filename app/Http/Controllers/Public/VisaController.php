<?php

namespace App\Http\Controllers\Public;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Product;
use App\Models\VisaType;
use Illuminate\Http\Request;
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
    public function index(Request $request): View
    {
        $destination = $request->filled('destination')
            ? Country::query()->active()->where('slug', $request->string('destination'))->first()
            : null;

        $products = Product::query()->live()
            ->with(['country', 'visaType'])
            ->when($destination, fn ($q) => $q->where('country_id', $destination->id))
            ->when($request->filled('purpose'), fn ($q) => $q->whereHas('visaType', fn ($t) => $t->where('slug', $request->string('purpose'))))
            ->orderBy('public_name')
            ->limit(50)
            ->get();
        // TODO(1.13): filter by applicant nationality (products.nationality_iso2 null = any) and show prices.

        return view('public.visa.index', ['products' => $products, 'destination' => $destination]);
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
