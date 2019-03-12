<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Book;
use Ddrv\Mailer\Message;

final class Fake implements TransportInterface
{

    public function send(Message $message, Book $addresses)
    {
        unset($message, $addresses);
        return true;
    }
}