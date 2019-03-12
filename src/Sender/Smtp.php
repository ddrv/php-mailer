<?php

namespace Ddrv\Mailer\Sender;

use Ddrv\Mailer\Message;

final class Smtp implements SenderInterface
{

    const ENCRYPTION_TLS = 'tls';

    const ENCRYPTION_SSL = 'ssl';

    /**
     * @var resource
     */
    private $socket;

    /**
     * @var string
     */
    private $sender;

    /**
     * Smtp constructor.
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $password
     * @param string $sender
     * @param string $encryption
     * @param string $domain
     */
    public function __construct($host, $port, $user, $password, $sender, $encryption=self::ENCRYPTION_TLS, $domain='')
    {
        $this->sender = $sender;
        $host = (string)$host;
        $port = (int)$port;
        $user = (string)$user;
        $password = (string)$password;
        $domain = (string)$domain;
        if ($host && $port) {
            if (in_array($encryption, array(self::ENCRYPTION_TLS, self::ENCRYPTION_SSL))) {
                $host = $encryption.'://'.$host;
            }
            $this->socket = fsockopen((string)$host, (int)$port, $errCode, $errMessage, 30);
            $test = fgets($this->socket, 512);
            unset($test);

            $this->smtpCommand("EHLO $domain");
            $this->smtpCommand("AUTH LOGIN");
            $this->smtpCommand(base64_encode($user));
            $this->smtpCommand(base64_encode($password));
        }
    }

    public function send(Message $message, $addresses)
    {
        $this->smtpCommand("MAIL FROM: <{$this->sender}>");
        foreach ($addresses as $address) {
            $this->smtpCommand("RCPT TO: <$address>");
        }
        $this->smtpCommand("DATA");
        $headers = "SUBJECT: {$message->getSubject()}\r\n{$message->getHeadersLine()}";
        $this->smtpCommand("$headers\r\n\r\n{$message->getBody()}\r\n.");
        return true;
    }



    /**
     * @param string $command
     * @return string
     */
    private function smtpCommand($command)
    {
        $response = false;
        if ($this->socket) {
            fputs($this->socket, $command."\r\n");
            $response = fgets($this->socket, 512);
        }
        return $response;
    }

    public function __destruct()
    {
        $this->smtpCommand("QUIT");
        fclose($this->socket);
    }
}