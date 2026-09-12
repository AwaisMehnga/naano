<?php

namespace App\Enums;

enum WalletTransactionType: string
{
    case Topup = 'topup';
    case Hold = 'hold';
    case Capture = 'capture';
    case Release = 'release';
    case Refund = 'refund';
    case Payout = 'payout';
    case PlatformFee = 'platform_fee';
}
