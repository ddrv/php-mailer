<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Message;

final class File implements TransportInterface
{
    /**
     * @var callable
     */
    private $logger;

    /**
     * @var string
     */
    private $dir;

    /**
     * @var string
     */
    private $sender;

    public function __construct($sender, $dir)
    {
        $this->sender = $sender;
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $this->dir = $dir;
    }

    public function send(Message $message, $recipients)
    {
        $content = "{$message->getHeadersLine()}\r\n\r\n{$message->getBody()}";
        if (is_callable($this->logger)) {
            $logger = $this->logger;
            $logger($content);
        }
        foreach ($recipients as $email) {
            $arr = explode("@", $email);
            $user = $arr[0];
            $host = $arr[1];
            $dir = implode(DIRECTORY_SEPARATOR, array($this->dir, $host, $user));
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            $num = 1;
            do {
                $prefix = "mail_" . date(DATE_ATOM);
                $suffix = str_pad($num, 3, "0", STR_PAD_LEFT);
                $file = implode(DIRECTORY_SEPARATOR, array($dir, "{$prefix}_{$suffix}.eml"));
                $num++;
            } while (is_file($file));
            file_put_contents($file, $content);
        }
        return true;
    }

    public function getSender()
    {
        return $this->sender;
    }

    public function setLogger(callable $logger)
    {
        $this->logger = $logger;
    }
}