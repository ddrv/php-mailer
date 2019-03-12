<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Book;
use Ddrv\Mailer\Message;

interface TransportInterface
{
    /**
     * Send mail
     *
     * @param Message $message
     * @param Book $addresses
     * @return bool
     */
    public function send(Message $message, Book $addresses);
}

