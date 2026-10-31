<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Phase 1: role-gated dashboard shells. Each portal is filled in by its
 * own phase (client: 3-8, agency: 9, admin: 11).
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

    public function admin(): View
    {
        return view('dashboards.shell', ['portal' => 'Admin', 'title' => 'Admin Dashboard']);
    }

    public function pending(Request $request): View
    {
        return view('auth.pending', ['agency' => $request->user()->agency]);
    }
}
