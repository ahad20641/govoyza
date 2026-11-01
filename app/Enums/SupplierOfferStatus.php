<?php

namespace App\Enums;

enum SupplierOfferStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case ChangesRequested = 'changes_requested';
    case Live = 'live';
    case Rejected = 'rejected';
    case Paused = 'paused';
}
