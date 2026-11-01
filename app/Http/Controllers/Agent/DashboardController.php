<?php

namespace App\Http\Controllers\Agent;

use App\Domain\Agencies\Wallet;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Agency dashboard: orders by status, wallet balance, monthly spend.
 * Agent portal, govoyza.com/agent. Phase 2 — Agent portal.
 */
class DashboardController extends Controller
{
    public function show(Request $request, Wallet $wallet): View
    {
        $agency = $request->user()->currentAgency();

        return view('agent.dashboard', [
            'agency' => $agency,
            'balance' => $agency ? $wallet->balance($agency) : null,
            'orders' => Order::query()->visibleTo($request->user())->with('product.country')->latest()->limit(10)->get(),
        ]);
    }
}
