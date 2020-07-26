<?php

namespace Ddrv\Mailer\Exception;

use InvalidArgumentException;

final class HeaderNotModifiedException extends InvalidArgumentException
{

    public function __construct($header, $method = null)
    {
        $message = 'header "' . $header . '" is not modified.';
        $code = 2;
        if ($method) {
            $message .= ' use ' . $method . ' method for it.';
            $code = 1;
        }
        parent::__construct($message, $code);
    }
}
