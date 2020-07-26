<?php

namespace Stuff\Ddrv\Mailer\Transport;

use Closure;
use Ddrv\Mailer\Contract\Message;
use Ddrv\Mailer\Contract\Transport;
use Ddrv\Mailer\Exception\RecipientsListEmptyException;

final class MockTransport implements Transport
{
    /**
     * @var Closure
     */
    private $logger;

    /**
     * @var Message[]
     */
    private $messages = array();

    public function __construct(Closure $logger = null)
    {
        $this->logger = $logger;
    }

    public function send(Message $message)
    {
        if (!$message->getRecipients()) {
            throw new RecipientsListEmptyException();
        }
        if (is_callable($this->logger)) {
            $logger = $this->logger;
            $content = $message->getHeadersRaw() . "\r\n\r\n" . $message->getBodyRaw();
            $logger($content);
        }
        $this->messages[] = $message;
        return true;
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
