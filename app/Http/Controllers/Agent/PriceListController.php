<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\View\View;

/**
 * B2B net price list of live products for the agency's tier.
 * Agent portal, govoyza.com/agent. Task 1.12 — Pricing engine (prices), Phase 2 — Agent portal.
 */
class PriceListController extends Controller
{
    public function index(): View
    {
        // TODO(1.12): net price per product from PriceCalculator for the agency's tier.
        $products = Product::query()->live()->with(['country', 'visaType'])->orderBy('public_name')->paginate(50);

        return view('agent.prices', ['products' => $products]);
    }
}
