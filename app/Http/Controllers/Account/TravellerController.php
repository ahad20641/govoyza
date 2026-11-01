<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Saved travellers: passport details stored once, reused on new orders.
 * Customer portal, govoyza.com/account. Task 1.22 — Customer portal.
 */
class TravellerController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.travellers', ['travellers' => $request->user()->savedTravellers()->orderBy('given_names')->get()]);
    }
}
