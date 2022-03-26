<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Contract\Message;
use Ddrv\Mailer\Contract\Transport;

final class SendmailTransport implements Transport
{
    /**
     * @var string
     */
    private $options;

    public function __construct($options = '')
    {
        $this->options = (string)$options;
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message)
    {
        return mail(
            implode(', ', $message->getRecipients()),
            '',
            $message->getBodyRaw(),
            $message->getHeadersRaw(),
            $this->options
        );
    }
}
