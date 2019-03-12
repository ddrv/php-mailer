<?php

namespace Ddrv\Mailer\Sender;

use Ddrv\Mailer\Message;

final class Legacy implements SenderInterface
{

    private $options;

    public function __construct($options = '')
    {
        $this->options = (string)$options;
    }

    public function send(Message $message, $addresses)
    {
        $to = implode(',', $addresses);
        $subject = $message->getSubject();
        $body = $message->getBody();
        $headers = $message->getHeadersLine();
        return mail($to, $subject, $body, $headers, $this->options);
    }
}