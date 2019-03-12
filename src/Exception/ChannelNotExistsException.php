<?php

namespace Ddrv\Mailer\Exception;

use Exception;
use Throwable;

class ChannelNotExistsException extends Exception
{

    public function __construct($name, Throwable $previous = null)
    {
        parent::__construct("channel $name not exists", 3, $previous);
    }
}