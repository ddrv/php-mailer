<?php

namespace Ddrv\Mailer\Exception;

use Exception;
use Throwable;

class ChannelCantBeRemovedException extends Exception
{

    public function __construct($name, Throwable $previous = null)
    {
        parent::__construct("channel $name can't be removed", 2, $previous);
    }
}