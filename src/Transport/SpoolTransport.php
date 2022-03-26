<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Contract\Message;
use Ddrv\Mailer\Contract\Spool;
use Ddrv\Mailer\Contract\Transport;
use Exception;

final class SpoolTransport implements Transport
{
    /**
     * @var Transport
     */
    private $transport;

    /**
     * @var Spool
     */
    private $spool;

    public function __construct(Transport $transport, Spool $spool)
    {
        $this->transport = $transport;
        $this->spool = $spool;
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message)
    {
        $this->spool->push($message, 1);
        return true;
    }

    /**
     * @param int $limit
     * @param int $tries
     */
    public function flush($limit = 100, $tries = 5)
    {
        $tries = (int)$tries;
        if ($tries < 1) {
            $tries = 1;
        }
        $limit = (int)$limit;
        if ($limit < 1) {
            $limit = 1;
        }
        $num = 0;
        for ($attempt = 1; $attempt <= $tries; $attempt++) {
            do {
                $num++;
                $message = $this->spool->pull($attempt);
                if (!$message instanceof Message) {
                    $message = null;
                }
                if (!$message) {
                    continue;
                }
                try {
                    $success = $this->transport->send($message);
                } catch (Exception $e) {
                    $success = false;
                }
                if (!$success) {
                    $this->spool->push($message, $attempt + 1);
                }
            } while ($message && $num < $limit);
        }
    }
}
