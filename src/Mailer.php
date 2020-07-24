<?php

namespace Ddrv\Mailer;

use Exception;

final class Mailer
{

    const MAILER_VERSION = "4.1.5";

    /**
     * @var SpoolInterface
     */
    private $spool;

    /**
     * @var string
     */
    private $from;

    /**
     * Mailer constructor.
     * @param SpoolInterface $spool
     * @param string $from
     */
    public function __construct(SpoolInterface $spool, $from = "")
    {
        $this->spool = $spool;
        $this->from = $from;
    }

    public function flush($limit = 0)
    {
        $this->spool->flush($limit);
    }

    /**
     * @param Message $message
     * @param int $priority
     * @return self
     */
    public function send(Message $message, $priority = 0)
    {
        return $this->sendMail($message, false, $priority);
    }

    /**
     * @param Message $message
     * @param int $priority
     * @return self
     */
    public function personal(Message $message, $priority = 0)
    {
        return $this->sendMail($message, true, $priority);
    }

    /**
     * @param Message $message
     * @param bool $personal
     * @param int $priority
     * @return self
     */
    private function sendMail(Message $message, $personal = false, $priority = 1)
    {
        if ($this->from) {
            $message->setHeader("From", $this->from);
        }
        $priority = (int)$priority;
        $params = array();
        if ($priority < 1) {
            $priority = 0;
        }
        if ($priority) {
            $fn = array($this->spool, "add");
            $params[1] = $priority;
        } else {
            $fn = array($this->spool, "send");
        }
        $messages = $personal ? $message->getPersonalMessages() : array($message);
        foreach ($messages as $msg) {
            $params[0] = $msg;
            ksort($params);
            try {
                call_user_func_array($fn, $params);
            } catch (Exception $e) {
            }
        }
        return $this;
    }
}
