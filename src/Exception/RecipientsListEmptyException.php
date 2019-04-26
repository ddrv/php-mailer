<?php

namespace Ddrv\Mailer\Exception;

use Exception;
use Throwable;

class RecipientsListEmptyException extends Exception
{

    public function __construct(Throwable $previous = null)
    {
        parent::__construct("recipients list is empty", 1, $previous);
    }
}