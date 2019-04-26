<?php

namespace Ddrv\Mailer;

use Closure;
use Ddrv\Mailer\Exception\RecipientsListEmptyException;

interface TransportInterface
{

    /**
     * Send mail
     *
     * @param Message $message
     * @return bool
     * @throws RecipientsListEmptyException
     */
    public function send(Message $message);

    /**
     * @param Closure $logger
     * @return void
     */
    public function setLogger(Closure $logger);
}

