<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Track an application with order number + email, no login. Rate-limited in routes/web.php.
 * Public site, govoyza.com. Task 1.16 — Track application.
 */
class TrackController extends Controller
{
    public function show(): View
    {
        return view('public.track');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $request->validate([
            'number' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        // TODO(1.16): match number + email, show status timeline only. Same reply whether or not the order
        // exists, so the form can't be used to discover order numbers.
        return back()->withInput()->with('status', 'Tracking is coming in task 1.16.');
    }
}
