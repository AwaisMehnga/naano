<?php

namespace App\Enums;

enum ContractStatus: string
{
    case Generated = 'generated';
    case Active = 'active';
    case Void = 'void';
}
