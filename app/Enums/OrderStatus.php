<?php

namespace App\Enums;

/**
 * Order status, one value per row of planning/order-lifecycle.md. Customer portal, admin, partner jobs. Task 1.18 — Order state machine (transitions live in App\Domain\Orders\OrderStateMachine).
 */
enum OrderStatus: string
{
    case Placed = 'placed';
    case Paid = 'paid';
    case DocumentsPending = 'documents_pending';
    case UnderReview = 'under_review';
    case Assigned = 'assigned';
    case InProcess = 'in_process';
    case Appointment = 'appointment';
    case Decision = 'decision';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
