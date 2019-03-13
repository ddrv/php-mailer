<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Message;

final class Fake implements TransportInterface
{
    /**
     * @var callable
     */
    private $logger;

    public function send(Message $message, $recipients)
    {
        if (is_callable($this->logger)) {
            $logger = $this->logger;
            $log = "{$message->getHeadersLine()}\r\n\r\n{$message->getBody()}";
            $logger($log);
        }
        unset($recipients);
        return true;
    }

    public function getSender()
    {
        return "";
    }

    public function setLogger(callable $logger)
    {
        $this->logger = $logger;
    }
}