<?php

namespace App\Enums;

/**
 * Status of a partner job. Partner portal. Tasks 1.20 — Routing & jobs, 1.21 — Partner job updates.
 */
enum ServiceJobStatus: string
{
    case Assigned = 'assigned';
    case InProcess = 'in_process';
    case Appointment = 'appointment';
    case Decision = 'decision';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
