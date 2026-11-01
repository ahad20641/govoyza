<?php

namespace App\Enums;

enum OfferRevisionStatus: string
{
    case Pending = 'pending';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
