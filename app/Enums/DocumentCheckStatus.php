<?php

namespace App\Enums;

/**
 * Review state of an uploaded document. Customer portal and admin. Task 1.19 — Document upload & review.
 */
enum DocumentCheckStatus: string
{
    case Pending = 'pending';
    case Ok = 'ok';
    case Flagged = 'flagged';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
