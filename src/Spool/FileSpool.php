<?php

namespace Ddrv\Mailer\Spool;

use Ddrv\Mailer\Exception\RecipientsListEmptyException;
use Ddrv\Mailer\Message;
use Ddrv\Mailer\SpoolInterface;
use Ddrv\Mailer\TransportInterface;

final class FileSpool implements SpoolInterface
{

    /**
     * @var TransportInterface;
     */
    private $transport;

    /**
     * @var string
     */
    private $dir;

    public function __construct(TransportInterface $transport, $dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0644, true);
        }
        $this->dir = $dir;
        $this->transport = $transport;
    }

    /**
     * @param Message $message
     * @return self
     * @throws RecipientsListEmptyException
     */
    public function send(Message $message)
    {
        $this->transport->send($message);
        return $this;
    }

    /**
     * @param Message $message
     * @param int $priority
     * @return self
     * @throws RecipientsListEmptyException
     */
    public function add(Message $message, $priority = 0)
    {
        if (!$message->getRecipients()) {
            throw new RecipientsListEmptyException();
        }
        $priority = str_pad((int)$priority, 3, "0", STR_PAD_LEFT);
        $num = 1;
        $content = base64_encode(serialize($message));
        do {
            $prefix = "mail_" . $priority . "_" . date("YmdHis");
            $suffix = str_pad($num, 3, "0", STR_PAD_LEFT);
            $file = implode(DIRECTORY_SEPARATOR, array($this->dir, "{$prefix}_{$suffix}.eml"));
            $num++;
        } while (is_file($file));
        file_put_contents($file, $content);
        return $this;
    }

    /**
     * @param int $limit
     */
    public function flush($limit = 0)
    {
        $limit = (int)$limit;
        if ($limit < 1) {
            $limit = 0;
        }
        $send = 0;
        foreach (glob(implode(DIRECTORY_SEPARATOR, array($this->dir, "mail_???_??????????????_???.eml"))) as $file) {
            if ($limit && $send >= $limit) {
                return;
            }
            $message = unserialize(base64_decode(file_get_contents($file)));
            try {
                $this->transport->send($message);
            } catch (RecipientsListEmptyException $e) {
            }
            unlink($file);
            $send++;
        }
    }
}
