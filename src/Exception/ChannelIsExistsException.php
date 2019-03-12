<?php

namespace Ddrv\Mailer\Exception;

use Exception;
use Throwable;

class ChannelIsExistsException extends Exception
{

    public function __construct($name, Throwable $previous = null)
    {
        parent::__construct("channel $name already exists", 1, $previous);
    }
}