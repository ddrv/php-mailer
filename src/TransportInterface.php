<?php

namespace Ddrv\Mailer;

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
     * @param callable $logger
     * @return void
     */
    public function setLogger(callable $logger);
}

