<?php

namespace App\Exceptions;

use Exception;

class DuplicateJournalException extends Exception
{
    public function __construct($message = "A journal entry for this source event already exists. It must be reversed, not duplicated.")
    {
        parent::__construct($message);
    }
}
