<?php

namespace App\Domain\Orders;

use App\Domain\NotBuiltYet;
use App\Models\Order;
use App\Models\ServiceJob;

/**
 * Picks the supplier for an approved order: the primary live offer, else a backup when the primary is
 * paused, at capacity or late on SLAs, and opens a ServiceJob with the cost snapshot. Admin (one click).
 * Task 1.20 — Routing & jobs.
 */
class JobRouter
{
    public function route(Order $order): ServiceJob
    {
        // TODO(1.20): offer selection rules from planning/blueprint.md ("Supplier listing & approval flow").
        throw NotBuiltYet::task('1.20', 'Supplier routing');
    }
}
