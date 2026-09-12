<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case SelfServe = 'self_serve';
    case Managed = 'managed';
}
