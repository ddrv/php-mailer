<?php

namespace Tests\Ddrv\Mailer\Support\Mock\Transport;

use Closure;
use Ddrv\Mailer\Exception\RecipientsListEmptyException;
use Ddrv\Mailer\Message;
use Ddrv\Mailer\TransportInterface;

final class MockTransport implements TransportInterface
{
    /**
     * @var Closure
     */
    private $logger;

    /**
     * @var Message[]
     */
    private $messages = array();

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
        $this->messages[] = $message;
        return true;
    }

    public function setLogger(Closure $logger)
    {
        $this->logger = $logger;
    }

    public function pull()
    {
        return $this->messages ? array_shift($this->messages) : null;
    }

    public function count()
    {
        return count($this->messages);
    }
}
