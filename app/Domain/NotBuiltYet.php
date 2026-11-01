<?php

namespace App\Domain;

use LogicException;

/**
 * Thrown by Sprint S1 stubs (pricing, state machine, ledger, wallet, routing) so a call to
 * unbuilt business logic fails loudly instead of silently doing nothing. Shared by all domains.
 * Sprint S1 — Skeleton. Remove once the last stub is filled in.
 */
class NotBuiltYet extends LogicException
{
    public static function task(string $task, string $what): self
    {
        return new self("{$what} is not built yet (coming in task {$task}).");
    }
}
