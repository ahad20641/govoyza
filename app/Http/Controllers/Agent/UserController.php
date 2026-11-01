<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Agency sub-users (staff logins under one agency).
 * Agent portal, govoyza.com/agent. Phase 2 — Sub-users.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $agency = $request->user()->currentAgency();

        return view('agent.users', ['agency' => $agency, 'users' => $agency ? $agency->users()->get() : collect()]);
    }
}
