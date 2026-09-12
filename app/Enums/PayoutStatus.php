<?php

namespace App\Enums;

enum PayoutStatus: string
{
    case Pending = 'pending';
    case InTransit = 'in_transit';
    case Paid = 'paid';
    case Failed = 'failed';
}
