<?php

namespace App\Enums;

/**
 * Who can read an order message. Customers and partners never share a thread; admin relays (CLAUDE.md rules 1 and 2). Task 1.22 — Customer portal.
 */
enum MessageAudience: string
{
    case Customer = 'customer';
    case Partner = 'partner';
    case Internal = 'internal';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
