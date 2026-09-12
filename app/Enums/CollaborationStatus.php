<?php

namespace App\Enums;

enum CollaborationStatus: string
{
    case Invited = 'invited';
    case Applied = 'applied';
    case Outreach = 'outreach';
    case Declined = 'declined';
    case Selected = 'selected';
    case Booked = 'booked';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
}
