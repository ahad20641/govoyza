<?php

namespace App\Http\Controllers\Agent;

use App\Domain\Agencies\Wallet;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Wallet: balance, top-up, credit limit and statement.
 * Agent portal, govoyza.com/agent. Phase 2 — Wallet and credit.
 */
class WalletController extends Controller
{
    public function show(Request $request, Wallet $wallet): View
    {
        $agency = $request->user()->currentAgency();

        return view('agent.wallet', [
            'agency' => $agency,
            'balance' => $agency ? $wallet->balance($agency) : null,
            'transactions' => $agency ? $agency->walletTransactions()->latest()->limit(50)->get() : collect(),
        ]);
    }
}
