<?php

namespace Ddrv\Mailer\Exception;

use InvalidArgumentException;

final class InvalidEmailException extends InvalidArgumentException
{

    public function __construct($email)
    {
        parent::__construct('email ' . $email . ' is invalid', 1);
    }
}
