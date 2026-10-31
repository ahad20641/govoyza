<?php

namespace App\Enums;

/**
 * Lifecycle of an application (spec §14 tracking timeline, §20 agency filters).
 * An application row only exists after payment, so it starts at PaymentConfirmed.
 */
enum ApplicationStatus: string
{
    case PaymentConfirmed = 'payment_confirmed';
    case UnderReview = 'under_review';
    case DocumentsPending = 'documents_pending';
    case Assigned = 'assigned';
    case SubmittedToEmbassy = 'submitted_to_embassy';
    case Processing = 'processing';
    case Completed = 'completed';
    case DocumentReady = 'document_ready';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PaymentConfirmed => 'Payment Confirmed',
            self::UnderReview => 'Under Review',
            self::DocumentsPending => 'Documents Pending',
            self::Assigned => 'Assigned',
            self::SubmittedToEmbassy => 'Submitted to Embassy',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
            self::DocumentReady => 'Passport/Document Ready',
            self::Delivered => 'Delivered/Collected',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled, self::Refunded], true);
    }
}
