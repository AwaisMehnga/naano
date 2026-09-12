<?php

namespace App\Enums;

enum CollaborationSource: string
{
    case Invite = 'invite';
    case Apply = 'apply';
    case Sourced = 'sourced';
}
