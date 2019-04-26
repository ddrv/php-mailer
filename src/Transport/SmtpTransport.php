<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Exception\RecipientsListEmptyException;
use Ddrv\Mailer\Message;
use Ddrv\Mailer\TransportInterface;

final class SmtpTransport implements TransportInterface
{

    const ENCRYPTION_TLS = "tls";

    const ENCRYPTION_SSL = "ssl";

    /**
     * @var resource
     */
    private $socket;

    /**
     * @var string
     */
    private $email;

    /**
     * @var callable
     */
    private $logger;

    /**
     * Smtp constructor.
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $password
     * @param string $email
     * @param string $encryption
     * @param string $domain
     */
    public function __construct($host, $port, $user, $password, $email, $encryption = self::ENCRYPTION_TLS, $domain = "")
    {
        $this->email = (string)$email;
        $host = (string)$host;
        $port = (int)$port;
        $user = (string)$user;
        $password = (string)$password;
        $domain = (string)$domain;
        if ($host && $port) {
            if (in_array($encryption, array(self::ENCRYPTION_TLS, self::ENCRYPTION_SSL))) {
                $host = "$encryption://$host";
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

    /**
     * @param Message $message
     * @return bool
     * @throws RecipientsListEmptyException
     */
    public function send(Message $message)
    {
        if (!$message->getRecipients()) {
            throw new RecipientsListEmptyException();
        }
        $this->smtpCommand("MAIL FROM: <{$this->email}>");
        foreach ($message->getRecipients() as $address) {
            $this->smtpCommand("RCPT TO: <$address>");
        }
        $this->smtpCommand("DATA");
        $data = $message->getRaw();
        $this->smtpCommand("$data\r\n.");
        return true;
    }

    /**
     * @param callable $logger
     */
    public function setLogger(callable $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $command
     * @return string
     */
    private function smtpCommand($command)
    {
        $response = false;
        if ($this->socket) {
            if (is_callable($this->logger)) {
                $logger = $this->logger;
                $logger("> $command");
            }
            fputs($this->socket, $command."\r\n");
            $response = fgets($this->socket, 512);
            if (is_callable($this->logger)) {
                $logger = $this->logger;
                $logger("< $response");
            }
        }
        return $response;
    }

    public function __destruct()
    {
        $this->smtpCommand("QUIT");
        fclose($this->socket);
    }
}