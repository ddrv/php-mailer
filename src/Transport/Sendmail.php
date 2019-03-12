<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Book;
use Ddrv\Mailer\Message;

final class Sendmail implements TransportInterface
{

    private $options;

    private $sender;

    public function __construct($sender, $options = '')
    {
        $this->sender = $sender;
        $this->options = (string)$options;
    }

    public function send(Message $message, $recipients)
    {
        $message->setHeader('From', $this->sender);
        $subject = $message->getSubject();
        $body = $message->getBody();
        $headers = $message->getHeadersLine();
        $to = implode(', ', $recipients);
        return mail($to, $subject, $body, $headers, $this->options);
    }
}