<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Contract\Message;
use Ddrv\Mailer\Contract\Transport;

final class FileTransport implements Transport
{
    /**
     * @var string
     */
    private $dir;

    public function __construct($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $this->dir = $dir;
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message)
    {
        $content = $message->getHeadersRaw() . "\r\n\r\n" . $message->getBodyRaw();
        foreach ($message->getRecipients() as $email) {
            $arr = explode('@', $email);
            $user = $arr[0];
            $host = $arr[1];
            $dir = implode(DIRECTORY_SEPARATOR, array($this->dir, $host, $user));
            if (!is_dir($dir)) {
                mkdir($dir, 0644, true);
            }
            $num = 1;
            do {
                $prefix = 'mail_' . date('YmdHis');
                $suffix = str_pad($num, 5, '0', STR_PAD_LEFT);
                $file = implode(DIRECTORY_SEPARATOR, array($dir, $prefix . '_' . $suffix . '.eml'));
                $num++;
            } while (file_exists($file));
            file_put_contents($file, $content);
        }
        return true;
    }
}
