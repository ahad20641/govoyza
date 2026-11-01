<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Customer applications list and detail (timeline, checklist, uploads, messages).
 * Customer portal, govoyza.com/account. Task 1.22 — Customer portal.
 */
class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()->visibleTo($request->user())->with('product.country')->latest()->paginate(20);

        return view('account.applications.index', ['orders' => $orders]);
    }

    public function show(Request $request, string $number): View
    {
        // Scoped lookup: another customer's order number is a 404, not a 403, so numbers can't be probed.
        $order = Order::query()->visibleTo($request->user())->where('number', $number)
            ->with(['product.country', 'product.visaType', 'applicants', 'statusHistory'])
            ->firstOrFail();

        return view('account.applications.show', ['order' => $order]);
    }
}
