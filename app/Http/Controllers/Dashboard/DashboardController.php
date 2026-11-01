<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Phase 1: role-gated dashboard shells. Each portal is filled in by its
 * own task (customer 1.22, partner 1.7, agency Phase 2). Staff use the
 * Filament admin console on the admin domain instead.
 */
class DashboardController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->dashboardRoute());
    }

    public function client(): View
    {
        return view('dashboards.shell', ['portal' => 'Client', 'title' => 'My Dashboard']);
    }

    public function agency(): View
    {
        return view('dashboards.shell', ['portal' => 'Agency', 'title' => 'Agency Dashboard']);
    }

    public function vendor(): View
    {
        return view('dashboards.shell', ['portal' => 'Vendor', 'title' => 'Vendor Dashboard']);
    }

    public function pending(Request $request): View
    {
        return view('auth.pending', ['agency' => $request->user()->agency]);
    }
}
