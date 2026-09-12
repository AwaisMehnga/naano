<?php

namespace App\Exceptions;

use Exception;

class InvalidStripeSignatureException extends Exception
{
    public function __construct()
    {
        parent::__construct('Invalid Stripe signature.');
    }
}
