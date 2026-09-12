<?php

namespace App\Enums;

enum CollaborationEventType: string
{
    case StatusChange = 'status_change';
    case FollowUp = 'follow_up';
    case Note = 'note';
}
