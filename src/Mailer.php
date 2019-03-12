<?php

namespace Ddrv\Mailer;

use Ddrv\Mailer\Sender\Legacy;
use Ddrv\Mailer\Sender\SenderInterface;
use Ddrv\Mailer\Sender\Smtp;

final class Mailer
{

    /**
     * @var SenderInterface
     */
    private $sender;

    public function __construct()
    {
        $this->legacy();
    }

    public function legacy($options = '')
    {
        $this->sender = new Legacy($options);
    }

    public function smtp($host, $port, $user, $password, $sender, $encryption, $domain='')
    {
        $this->sender = new Smtp($host, $port, $user, $password, $sender, $encryption, $domain);
    }

    public function send(Message $message, $addresses, $personal = false)
    {
        if ($personal) {
            foreach ($addresses as $address) {
                $this->sender->send($message, array($address));
            }
        } else {
            $this->sender->send($message, $addresses);
        }
    }
}