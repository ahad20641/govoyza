<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sends a signed-in user to their own portal (/account, /agent, or the partner/admin panels), and shows
 * the "pending approval" page to agencies waiting for admin. govoyza.com. Tasks 1.5, S1.
 */
class DashboardController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->dashboardRoute());
    }

    public function pending(Request $request): View
    {
        return view('auth.pending', ['agency' => $request->user()->agency]);
    }
}
