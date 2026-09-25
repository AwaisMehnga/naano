<?php

namespace App\Enums;

enum ProfileType: string
{
    case Creator = 'creator';
    case Company = 'company';
    case Agency = 'agency';
    case Partner = 'partner';

    /**
     * @return list<self>
     */
    public static function activeCases(): array
    {
        return [self::Creator, self::Company];
    }

    public function isAvailable(): bool
    {
        return in_array($this, self::activeCases(), true);
    }
}
