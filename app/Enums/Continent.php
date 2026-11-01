<?php

namespace App\Enums;

enum Continent: string
{
    case Africa = 'africa';
    case Asia = 'asia';
    case Europe = 'europe';
    case NorthAmerica = 'north_america';
    case SouthAmerica = 'south_america';
    case Oceania = 'oceania';
    case Antarctica = 'antarctica';

    public function label(): string
    {
        return match ($this) {
            self::Africa => 'Africa',
            self::Asia => 'Asia',
            self::Europe => 'Europe',
            self::NorthAmerica => 'North America',
            self::SouthAmerica => 'South America',
            self::Oceania => 'Oceania',
            self::Antarctica => 'Antarctica',
        };
    }
}
