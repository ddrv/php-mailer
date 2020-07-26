<?php

namespace Ddrv\Mailer\Exception;

use InvalidArgumentException;

final class InvalidAttachmentNameException extends InvalidArgumentException
{

    public function __construct($name)
    {
        parent::__construct('invalid attachment ' . $name, 1);
    }
}
