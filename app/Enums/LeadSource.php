<?php

namespace App\Enums;

enum LeadSource: string
{
    case Click = 'click';
    case Form = 'form';
    case Manual = 'manual';
}
