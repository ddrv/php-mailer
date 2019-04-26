<?php

namespace Ddrv\Mailer\Transport;

use Closure;
use Ddrv\Mailer\Exception\RecipientsListEmptyException;
use Ddrv\Mailer\Message;
use Ddrv\Mailer\TransportInterface;

final class FakeTransport implements TransportInterface
{
    /**
     * @var callable
     */
    private $logger;

    public function send(Message $message)
    {
        if (!$message->getRecipients()) {
            throw new RecipientsListEmptyException();
        }
        if (is_callable($this->logger)) {
            $logger = $this->logger;
            $content = $message->getRaw();
            $logger($content);
        }
        unset($message);
        return true;
    }

    public function setLogger(Closure $logger)
    {
        $this->logger = $logger;
    }
}