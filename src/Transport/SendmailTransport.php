<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Contract\Message;
use Ddrv\Mailer\Contract\Transport;
use Ddrv\Mailer\Exception\RecipientsListEmptyException;

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

    public function send(Message $message)
    {
        if (!$message->getRecipients()) {
            throw new RecipientsListEmptyException();
        }
        return mail(
            implode(', ', $message->getRecipients()),
            $message->getBodyRaw(),
            $message->getBodyRaw(),
            $message->getHeadersRaw(),
            $this->options
        );
    }
}
