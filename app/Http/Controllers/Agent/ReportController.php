<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Commission and spend reports for the agency.
 * Agent portal, govoyza.com/agent. Phase 2 — Reports.
 */
class ReportController extends Controller
{
    public function index(): View
    {
        return view('agent.reports');
    }
}
