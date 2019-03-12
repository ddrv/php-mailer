<?php

namespace Ddrv\Mailer\Sender;

use Ddrv\Mailer\Message;

interface SenderInterface
{
    /**
     * Send mail
     *
     * @param Message $message
     * @param string[] $addresses
     * @return bool
     */
    public function send(Message $message, $addresses);
}

