<?php

namespace Ddrv\Mailer\Transport;

use Closure;
use Ddrv\Mailer\Contract\Message;
use Ddrv\Mailer\Contract\Transport;
use Ddrv\Mailer\Exception\TransportException;

final class SmtpTransport implements Transport
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
     * @var bool
     */
    private $tls = false;

    /**
     * @var Closure|null
     */
    private $requestLogger;

    /**
     * @var Closure|null
     */
    private $responseLogger;

    /**
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $pass
     * @param string $sender
     * @param string $encryption
     * @param string $domain
     */
    public function __construct(
        $host,
        $port,
        $user,
        $pass,
        $sender,
        $encryption = self::ENCRYPTION_TLS,
        $domain = ''
    ) {
        $this->sender = (string)$sender;
        $host = (string)$host;
        $port = (int)$port;
        $user = (string)$user;
        $pass = (string)$pass;
        $domain = (string)$domain;
        $encryption = trim(mb_strtolower($encryption));
        if ($host && $port) {
            $scheme = $encryption === self::ENCRYPTION_SSL ? 'ssl' : 'tcp';
            if ($encryption === self::ENCRYPTION_TLS) {
                $this->tls = true;
            }
            $this->connectHost = $scheme . '://' . $host;
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
        $addr = $this->connectHost . ':' . $this->connectPort;
        $this->socket = stream_socket_client($addr, $errCode, $errMessage, 10);
        if (!$this->socket) {
            throw new TransportException('Connection Error: ' . $errCode . ' ' . $errMessage, 1);
        }
        stream_set_timeout($this->socket, -1);
        $test = $this->read();

        unset($test);
        $options = $this->options();
        if (array_key_exists('STARTTLS', $options) && $this->tls) {
            $this->smtpCommand('STARTTLS');
            $mask = STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT
                | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_SSLv23_CLIENT;
            stream_socket_enable_crypto($this->socket, true, $mask);
            $options = $this->options();
        }
        $types = array('PLAIN', 'LOGIN');
        $supported = array_key_exists('AUTH', $options) ? $options['AUTH'] : array();
        $types = array_intersect($types, $supported);
        if ($types) {
            $this->auth(array_shift($types));
        }
    }

    private function auth($type)
    {
        switch ($type) {
            case 'PLAIN':
                $commands = array(base64_encode("\0" . $this->connectUser . "\0" . $this->connectPassword));
                break;
            case 'LOGIN':
                $commands = array(
                    base64_encode($this->connectUser),
                    base64_encode($this->connectPassword),
                );
                break;
            default:
                throw new TransportException('Unsupported auth type ' . $type, 3);
        }
        $this->smtpCommand('AUTH ' . $type);
        $response = array(
            array(
                'code' => 500,
                'message' => 'Unknown error',
            )
        );
        foreach ($commands as $command) {
            $response = $this->smtpCommand($command);
        }
        if ($response[0]['code'] !== 235) {
            throw new TransportException($response[0]['message'], $response[0]['code']);
        }
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message)
    {
        if (!$this->socket) {
            $this->connect();
        }
        $this->smtpCommand('MAIL FROM: <' . $this->sender . '>');
        foreach ($message->getRecipients() as $address) {
            $this->smtpCommand('RCPT TO: <' . $address . '>');
        }
        $this->smtpCommand('DATA');
        $data = $message->getHeadersRaw() . "\r\n\r\n" . $message->getBodyRaw() . "\r\n.\r\n";
        $this->smtpCommand($data);
        return true;
    }

    /**
     * @param string $command
     * @return string
     */
    private function smtpCommand($command)
    {
        $logger = $this->requestLogger;
        if (is_callable($logger)) {
            $logger($command);
        }
        $response = false;
        if ($this->socket) {
            fputs($this->socket, $command . "\r\n");
            $response = $this->read();
        }
        return $response;
    }

    /**
     * @return array[]
     */
    private function read()
    {
        $response = fgets($this->socket, 512);
        do {
            $meta = stream_get_meta_data($this->socket);
            $unread = $meta['unread_bytes'];
            if ($unread) {
                $response .= fgets($this->socket, $unread + 512);
            }
        } while ($unread);
        $logger = $this->responseLogger;
        if (is_callable($logger)) {
            $logger($response);
        }
        $stack = array();
        foreach (explode("\r\n", $response) as $line) {
            $stack[] = array(
                'code' => (int)substr($line, 0, 3),
                'message' => substr($line, 4),
                'option' => substr($line, 3, 1) === '-',
            );
        }
        return $stack;
    }

    /**
     * @return array
     */
    private function options()
    {
        $options = array();
        $data = $this->smtpCommand('EHLO ' . $this->connectDomain);
        foreach ($data as $row) {
            if ($row['option']) {
                $arr = explode(' ', $row['message']);
                $option = array_shift($arr);
                $options[$option] = $arr ? $arr : $option;
            }
        }
        return $options;
    }

    public function __destruct()
    {
        if (is_resource($this->socket)) {
            $this->smtpCommand('QUIT');
            fclose($this->socket);
        }
    }

    public function setRequestLogger(Closure $logger = null)
    {
        $this->requestLogger = $logger;
    }

    public function setResponseLogger(Closure $logger = null)
    {
        $this->responseLogger = $logger;
    }
}
