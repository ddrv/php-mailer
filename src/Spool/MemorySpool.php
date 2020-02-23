<?php

namespace Ddrv\Mailer\Spool;

use Ddrv\Mailer\Exception\RecipientsListEmptyException;
use Ddrv\Mailer\Message;
use Ddrv\Mailer\SpoolInterface;
use Ddrv\Mailer\TransportInterface;

final class MemorySpool implements SpoolInterface
{

    /**
     * @var Message[][]
     */
    private $messages = array();

    /**
     * @var TransportInterface;
     */
    private $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * @param Message $message
     * @param int $priority
     * @return self
     * @throws RecipientsListEmptyException
     */
    public function add(Message $message, $priority = 0)
    {
        if (!$message->getRecipients()) {
            throw new RecipientsListEmptyException();
        }
        $this->messages[$priority][] = $message;
        return $this;
    }

    /**
     * @param Message $message
     * @return self
     * @throws RecipientsListEmptyException
     */
    public function send(Message $message)
    {
        $this->transport->send($message);
        return $this;
    }

    /**
     * @param int $limit
     */
    public function flush($limit = 0)
    {
        $limit = (int)$limit;
        if ($limit < 1) {
            $limit = 0;
        }
        $send = 0;
        ksort($this->messages);
        foreach ($this->messages as $priority => $messages) {
            foreach ($messages as $key => $message) {
                if ($limit && $send >= $limit) {
                    return;
                }
                try {
                    $this->transport->send($message);
                } catch (RecipientsListEmptyException $e) {
                }
                unset($this->messages[$priority][$key]);
                $send++;
            }
        }
    }
}
