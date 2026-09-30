<?php

namespace App\Exceptions;

use Exception;

class UnbalancedJournalException extends Exception
{
    public function __construct($message = "Journal entry debits and credits must be exactly equal.")
    {
        parent::__construct($message);
    }
}
