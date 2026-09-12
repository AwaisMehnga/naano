<?php

namespace App\Exceptions;

use Exception;

class WalletUnderfundedException extends Exception
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload)
    {
        parent::__construct('The wallet does not have enough funds to book this creator.');
    }
}
