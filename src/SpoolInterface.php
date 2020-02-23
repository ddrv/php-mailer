<?php

namespace Ddrv\Mailer;

use Ddrv\Mailer\Exception\RecipientsListEmptyException;

interface SpoolInterface
{

    /**
     * @param Message $message
     * @return self
     * @throws RecipientsListEmptyException
     */
    public function send(Message $message);

    /**
     * @param Message $message
     * @param int $priority
     * @return self
     * @throws RecipientsListEmptyException
     */
    public function add(Message $message, $priority = 0);

    /**
     * @param null|int $limit
     * @return void
     */
    public function flush($limit = null);
}
