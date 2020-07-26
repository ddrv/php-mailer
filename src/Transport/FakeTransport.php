<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Contract\Message;
use Ddrv\Mailer\Contract\Transport;
use Ddrv\Mailer\Exception\RecipientsListEmptyException;

final class FakeTransport implements Transport
{

    public function send(Message $message)
    {
        if (!$message->getRecipients()) {
            throw new RecipientsListEmptyException();
        }
        return true;
    }
}
