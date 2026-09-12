<?php

namespace App\Enums;

enum CampaignType: string
{
    case ThoughtLeadership = 'thought_leadership';
    case Product = 'product';
    case Hiring = 'hiring';
    case Event = 'event';
    case Other = 'other';
}
