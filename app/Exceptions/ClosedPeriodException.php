<?php

namespace App\Exceptions;

use Exception;

class ClosedPeriodException extends Exception
{
    public function __construct($message = "Cannot post or modify journal entries in a closed accounting period.")
    {
        parent::__construct($message);
    }
}
