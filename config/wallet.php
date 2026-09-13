<?php

return [

    'topup_min_cents' => (int) env('WALLET_TOPUP_MIN_CENTS', 5000),
    'withdraw_min_cents' => (int) env('WALLET_WITHDRAW_MIN_CENTS', 10000),

];
