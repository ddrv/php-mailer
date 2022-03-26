<?php

namespace Ddrv\Mailer;

use Ddrv\Mailer\Contract\Message;
use Ddrv\Mailer\Contract\Transport;
use Ddrv\Mailer\Exception\RecipientsListEmptyException;
use Ddrv\Mailer\Exception\TransportException;

final class Mailer
{
    const MAILER_VERSION = "5.0.2";

    /**
     * @var string|null
     */
    private $senderEmail;

    /**
     * @var string|null
     */
    private $senderName;

    /**
     * @var Transport
     */
    private $transport;

    /**
     * @param Transport $transport
     * @param string|null $senderEmail
     * @param string|null $senderName
     */
    public function __construct(Transport $transport, $senderEmail = null, $senderName = null)
    {
        $this->transport = $transport;
        $senderEmail = (string)$senderEmail;
        $senderName = (string)$senderName;
        $this->senderEmail = $senderEmail ? $senderEmail : null;
        $this->senderName = $senderName ? $senderName : null;
    }

    /**
     * @param Message $message
     * @return bool
     * @throws RecipientsListEmptyException
     * @throws TransportException
     */
    public function send(Message $message)
    {
        return (bool)$this->sendMail($message, false);
    }

    /**
     * @param Message $message
     * @return int
     * @throws RecipientsListEmptyException
     */
    public function personal(Message $message)
    {
        return $this->sendMail($message, true);
    }

    /**
     * @param Message $message
     * @param bool $personal
     * @return int
     * @throws RecipientsListEmptyException
     */
    private function sendMail(Message $message, $personal = false)
    {
        if (!$message->getRecipients()) {
            throw new RecipientsListEmptyException();
        }
        if ($this->senderEmail) {
            $message->setSender($this->senderEmail, $this->senderName);
        }
        if ($personal) {
            $messages = $message->getPersonalMessages();
        } else {
            $messages = array($message);
        }
        $result = 0;
        foreach ($messages as $msg) {
            try {
                $this->transport->send($msg);
                $result++;
            } catch (TransportException $exception) {
                if (!$personal) {
                    throw $exception;
                }
            }
        }
        return $result;
    }
}
