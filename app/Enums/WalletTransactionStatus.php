<?php

namespace App\Enums;

enum WalletTransactionStatus: string
{
    case Pending = 'pending';
    case Posted = 'posted';
    case Failed = 'failed';
}
