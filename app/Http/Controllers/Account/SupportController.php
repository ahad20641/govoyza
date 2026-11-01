<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Support tickets and order messages with Govoyza (never with a partner).
 * Customer portal, govoyza.com/account. Task 1.22 — Customer portal.
 */
class SupportController extends Controller
{
    public function index(): View
    {
        return view('account.support');
    }
}
