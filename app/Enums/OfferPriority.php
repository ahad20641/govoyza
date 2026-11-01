<?php

namespace App\Enums;

enum OfferPriority: string
{
    case Primary = 'primary';
    case Backup = 'backup';
    case Other = 'other';
}
