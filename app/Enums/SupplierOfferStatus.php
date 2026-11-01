<?php

namespace App\Enums;

enum SupplierOfferStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Live = 'live';
    case Rejected = 'rejected';
    case Paused = 'paused';
}
