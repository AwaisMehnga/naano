<?php

namespace App\Enums;

enum CreatorVettingStatus: string
{
    case Pending = 'pending';
    case Vetted = 'vetted';
    case Rejected = 'rejected';
}
