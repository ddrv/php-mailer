<?php

namespace Ddrv\Mailer\Transport;

use Closure;
use Ddrv\Mailer\Message;

final class Sendmail implements TransportInterface
{

    private $options;

    private $sender;
    /**
     * @var callable
     */
    private $logger;

    public function __construct($sender, $options = "")
    {
        $this->sender = $sender;
        $this->options = (string)$options;
    }

    public function send(Message $message, $recipients)
    {
        $subject = $message->getSubject();
        $body = $message->getBody();
        $headers = $message->getHeadersLine();
        $to = implode(", ", $recipients);
        if (is_callable($this->logger)) {
            $logger = $this->logger;
            $log = "mail(";
            $log .= "\"".addslashes($to)."\", ";
            $log .= "\"".addslashes($subject)."\", ";
            $log .= "\"".addslashes($body)."\", ";
            $log .= "\"".addslashes($headers)."\", ";
            $log .= "\"".addslashes($this->options)."\", ";
            $log .= ");";
            $logger($log);
        }
        return mail($to, $subject, $body, $headers, $this->options);
    }

    public function getSender()
    {
        return $this->sender;
    }

    public function setLogger(Closure $logger)
    {
        $this->logger = $logger;
    }
}