<?php

namespace App\Domain\Orders;

use App\Enums\OrderStatus;
use DomainException;

/**
 * Raised when an order is asked to move between two statuses the lifecycle doesn't allow.
 * Task 1.18 — Order state machine (planning/order-lifecycle.md, "Rules").
 */
class InvalidTransition extends DomainException
{
    public static function between(OrderStatus $from, OrderStatus $to): self
    {
        return new self("An order cannot move from {$from->value} to {$to->value}.");
    }
}
