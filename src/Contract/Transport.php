<?php

namespace Ddrv\Mailer\Contract;

use Ddrv\Mailer\Exception\TransportException;

interface Transport
{
    /**
     * @param Message $message
     * @return bool
     * @throws TransportException
     */
    public function send(Message $message);
}
