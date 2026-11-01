<?php

namespace App\Http\Controllers\Public;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Nationality;
use App\Models\VisaType;
use Illuminate\View\View;

/**
 * Home page with the visa finder ("I am a [nationality] going to [country] for [purpose]").
 * Public site, govoyza.com. Task 1.13 — Home + visa finder (S1: form UI only).
 */
class HomeController extends Controller
{
    public function show(): View
    {
        return view('public.home', [
            'nationalities' => Nationality::query()->active()->with('country:id,iso2')
                ->orderBy('sort_order')->orderBy('demonym')->get(['id', 'country_id', 'demonym']),
            'destinations' => Country::query()->active()->orderBy('name')->get(['id', 'name', 'slug']),
            'visaTypes' => VisaType::query()->active()->get(['id', 'name', 'slug']),
            // Popular = countries with something live to sell; never a partner name or cost.
            'popular' => Country::query()
                ->whereHas('products', fn ($q) => $q->where('status', ProductStatus::Live))
                ->orderBy('name')->limit(8)->get(['id', 'name', 'slug', 'iso2']),
        ]);
    }
}
