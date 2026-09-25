<?php

namespace App\Enums;

enum LinkedInPostsSyncStatus: string
{
    case Idle = 'idle';
    case Syncing = 'syncing';
    case Ready = 'ready';
    case Failed = 'failed';
}
