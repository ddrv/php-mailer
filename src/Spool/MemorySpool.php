<?php

namespace Ddrv\Mailer\Spool;

use Ddrv\Mailer\Contract\Message;
use Ddrv\Mailer\Contract\Spool;

final class MemorySpool implements Spool
{

    /**
     * @var Message[][]
     */
    private $spool = array();

    /**
     * @inheritDoc
     */
    public function push(Message $message, $attempt)
    {
        $this->spool[$attempt][] = $message;
    }

    /**
     * @inheritDoc
     */
    public function pull($attempt)
    {
        if (!array_key_exists($attempt, $this->spool) || empty($this->spool[$attempt])) {
            return null;
        }
        return array_shift($this->spool[$attempt]);
    }
}
