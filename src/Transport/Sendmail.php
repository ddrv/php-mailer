<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Book;
use Ddrv\Mailer\Message;

final class Sendmail implements TransportInterface
{

    private $options;

    public function __construct($options = '')
    {
        $this->options = (string)$options;
    }

    public function send(Message $message, Book $addresses)
    {
        $subject = $message->getSubject();
        $body = $message->getBody();
        $headers = $headers = "{$message->getHeadersLine()}\r\nTo: {$addresses->getContacts()}";
        $rcpt = array();
        $rcpt[] = $addresses->getContacts();
        if (!$message->getCC()->isEmpty()) {
            $rcpt[] = $message->getCC()->getContacts();
        }
        if (!$message->getBCC()->isEmpty()) {
            $rcpt[] = $message->getBCC()->getContacts();
        }
        $to = implode(', ', $rcpt);
        return mail($to, $subject, $body, $headers, $this->options);
    }
}