<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Apply for a client, or several travellers at once (bulk/group form).
 * Agent portal, govoyza.com/agent. Task 1.17 — Order placement, Phase 2 — Bulk.
 */
class ApplicationController extends Controller
{
    public function create(): View
    {
        return view('agent.applications.create');
    }

    public function bulk(): View
    {
        return view('agent.applications.bulk');
    }
}
