<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Customer dashboard: applications with status bars and what needs doing next.
 * Customer portal, govoyza.com/account. Task 1.22 — Customer portal.
 */
class DashboardController extends Controller
{
    public function show(Request $request): View
    {
        $orders = Order::query()->visibleTo($request->user())->with('product.country')->latest()->limit(5)->get();

        return view('account.dashboard', ['orders' => $orders]);
    }
}
