<?php

namespace App\Enums;

enum PostReviewAction: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case ChangesRequested = 'changes_requested';
    case Rejected = 'rejected';
}
