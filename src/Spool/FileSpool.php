<?php

namespace Ddrv\Mailer\Spool;

use Ddrv\Mailer\Contract\Message;
use Ddrv\Mailer\Contract\Spool;

final class FileSpool implements Spool
{

    /**
     * @var string
     */
    private $dir;

    public function __construct($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0644, true);
        }
        $this->dir = $dir;
    }

    /**
     * @inheritDoc
     */
    public function push(Message $message, $attempt)
    {
        $priority = str_pad((int)$attempt, 3, '0', STR_PAD_LEFT);
        $num = 1;
        $content = base64_encode(serialize($message));
        $prefix = 'mail_' . $priority . '_' . date('YmdHis');
        do {
            $suffix = str_pad($num, 3, "0", STR_PAD_LEFT);
            $file = implode(DIRECTORY_SEPARATOR, array($this->dir, $prefix . '_' . $suffix . '.eml'));
            $num++;
        } while (file_exists($file));
        file_put_contents($file, $content);
    }

    /**
     * @inheritDoc
     */
    public function pull($attempt)
    {
        $priority = str_pad((int)$attempt, 3, '0', STR_PAD_LEFT);
        $files = glob(implode(DIRECTORY_SEPARATOR, array($this->dir, 'mail_' . $priority . '_??????????????_???.eml')));
        if (!$files) {
            return null;
        }
        do {
            $file = array_shift($files);
            $message = unserialize(base64_decode(file_get_contents($file)));
            unlink($file);
            if (!$message instanceof Message) {
                $message = null;
            }
        } while (!$message && $files);
        return $message;
    }
}
