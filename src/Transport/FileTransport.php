<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Exception\RecipientsListEmptyException;
use Ddrv\Mailer\Message;
use Ddrv\Mailer\TransportInterface;

final class FileTransport implements TransportInterface
{
    /**
     * @var callable
     */
    private $logger;

    /**
     * @var string
     */
    private $dir;

    public function __construct($dir)
    {
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $this->dir = $dir;
    }

    public function send(Message $message)
    {
        if (!$message->getRecipients()) {
            throw new RecipientsListEmptyException();
        }
        $content = $message->getRaw();
        if (is_callable($this->logger)) {
            $logger = $this->logger;
            $logger($content);
        }
        foreach ($message->getRecipients() as $email) {
            $arr = explode("@", $email);
            $user = $arr[0];
            $host = $arr[1];
            $dir = implode(DIRECTORY_SEPARATOR, array($this->dir, $host, $user));
            if (!is_dir($dir)) mkdir($dir, 0644, true);
            $num = 1;
            do {
                $prefix = "mail_" . date("YmdHis");
                $suffix = str_pad($num, 5, "0", STR_PAD_LEFT);
                $file = implode(DIRECTORY_SEPARATOR, array($dir, "{$prefix}_{$suffix}.eml"));
                $num++;
            } while (is_file($file));
            file_put_contents($file, $content);
        }
        return true;
    }

    public function setLogger(callable $logger)
    {
        $this->logger = $logger;
    }
}