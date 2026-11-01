<?php

namespace App\Enums;

/**
 * Embassy outcome recorded at the `decision` status, separate from the order status. Task 1.21 — Partner job updates.
 */
enum OrderDecision: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
    case AdditionalDocs = 'additional_docs';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
