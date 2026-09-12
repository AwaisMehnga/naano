<?php

namespace App\Enums;

enum CompanyMemberRole: string
{
    case Owner = 'owner';
    case Member = 'member';
}
