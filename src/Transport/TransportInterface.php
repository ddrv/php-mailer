<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Message;

interface TransportInterface
{

    /**
     * Send mail
     *
     * @param Message $message
     * @param string[] $recipients
     * @return bool
     */
    public function send(Message $message, $recipients);

    /**
     * @return string
     */
    public function getSender();

    /**
     * @param callable $logger
     * @return void
     */
    public function setLogger(callable $logger);
}

