<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Live = 'live';
    case Paused = 'paused';
}
