<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Profile and notification settings.
 * Customer portal, govoyza.com/account. Task 1.22 — Customer portal.
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.profile', ['user' => $request->user()]);
    }
}
