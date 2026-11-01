<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Payments and invoice PDFs for the customer's own orders.
 * Customer portal, govoyza.com/account. Task 1.25 — Invoices & receipts.
 */
class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->whereIn('order_id', Order::query()->visibleTo($request->user())->select('id'))
            ->with('order')->latest()->get();

        return view('account.payments', ['payments' => $payments]);
    }
}
