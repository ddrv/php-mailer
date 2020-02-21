<?php

namespace Ddrv\Mailer\Transport;

use Closure;
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
     * @var string
     */
    private $connectHost;

    /**
     * @var int
     */
    private $connectPort;

    /**
     * @var string
     */
    private $connectUser;

    /**
     * @var string
     */
    private $connectPassword;

    /**
     * @var string
     */
    private $connectDomain;

    /**
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $pass
     * @param string $email
     * @param string $encryption
     * @param string $domain
     */
    public function __construct($host, $port, $user, $pass, $email, $encryption = self::ENCRYPTION_TLS, $domain = "")
    {
        $this->email = (string)$email;
        $host = (string)$host;
        $port = (int)$port;
        $user = (string)$user;
        $pass = (string)$pass;
        $domain = (string)$domain;
        if ($host && $port) {
            if (in_array($encryption, array(self::ENCRYPTION_TLS, self::ENCRYPTION_SSL))) {
                $host = "$encryption://$host";
            }
            $this->connectHost = $host;
            $this->connectPort = $port;
            $this->connectUser = $user;
            $this->connectPassword = $pass;
            $this->connectDomain = $domain;
        }
    }

    private function connect()
    {
        if ($this->socket) {
            return;
        }
        $this->socket = fsockopen($this->connectHost, $this->connectPort, $errCode, $errMessage, 30);
        $test = fgets($this->socket, 512);
        unset($test);
        $this->smtpCommand("EHLO {$this->connectDomain}");
        $this->smtpCommand("AUTH LOGIN");
        $this->smtpCommand(base64_encode($this->connectUser));
        $this->smtpCommand(base64_encode($this->connectPassword));
    }

    /**
     * @param Message $message
     * @return bool
     * @throws RecipientsListEmptyException
     */
    public function send(Message $message)
    {
        if (!$this->socket) {
            $this->connect();
        }
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
     * @param Closure $logger
     */
    public function setLogger(Closure $logger)
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
            fputs($this->socket, "$command\r\n");
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
