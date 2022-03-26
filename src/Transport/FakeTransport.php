<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Contract\Message;
use Ddrv\Mailer\Contract\Transport;

final class FakeTransport implements Transport
{
    /**
     * @inheritDoc
     */
    public function send(Message $message)
    {
        return true;
    }
}
