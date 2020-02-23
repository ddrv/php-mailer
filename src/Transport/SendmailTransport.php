<?php

namespace Ddrv\Mailer\Transport;

use Closure;
use Ddrv\Mailer\Exception\RecipientsListEmptyException;
use Ddrv\Mailer\Message;
use Ddrv\Mailer\TransportInterface;

final class SendmailTransport implements TransportInterface
{

    /**
     * @var string
     */
    private $options;

    /**
     * @var callable
     */
    private $logger;

    public function __construct($options = "")
    {
        $this->options = (string)$options;
    }

    public function send(Message $message)
    {
        if (!$message->getRecipients()) {
            throw new RecipientsListEmptyException();
        }
        $subject = $message->getSubject();
        $body = $message->getBody();
        $headers = implode("\r\n", $message->getHeaders());
        $to = implode(", ", $message->getRecipients());
        if (is_callable($this->logger)) {
            $logger = $this->logger;
            $log = "mail(";
            $log .= "\"" . addslashes($to) . "\", ";
            $log .= "\"" . addslashes($subject) . "\", ";
            $log .= "\"" . addslashes($body) . "\", ";
            $log .= "\"" . addslashes($headers) . "\", ";
            $log .= "\"" . addslashes($this->options) . "\", ";
            $log .= ");";
            $logger($log);
        }
        return mail($to, $subject, $body, $headers, $this->options);
    }

    public function setLogger(Closure $logger)
    {
        $this->logger = $logger;
    }
}
