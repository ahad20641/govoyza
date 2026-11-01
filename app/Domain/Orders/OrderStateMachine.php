<?php

namespace App\Domain\Orders;

use App\Domain\NotBuiltYet;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

/**
 * The only way an order's status changes (CLAUDE.md rule 5). Checks the transition is allowed and the
 * actor may trigger it, writes order_status_history, then fires an event; listeners do the side effects
 * (invoice, ledger, notifications, routing). Customer, agent, partner and admin all call it.
 * Task 1.18 — Order state machine (planning/order-lifecycle.md).
 */
class OrderStateMachine
{
    public function transition(Order $order, OrderStatus $to, ?User $actor = null, ?string $reason = null): Order
    {
        // TODO(1.18): allowed-transition table from order-lifecycle.md, who-may-trigger checks,
        // lockForUpdate in a DB transaction, history row, OrderStatusChanged event. Throw InvalidTransition.
        throw NotBuiltYet::task('1.18', 'The order state machine');
    }

    /** @return list<OrderStatus> statuses reachable from the current one (for UI buttons) */
    public function allowedNext(Order $order): array
    {
        // TODO(1.18)
        return [];
    }
}
