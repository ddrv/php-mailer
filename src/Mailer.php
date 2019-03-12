<?php

namespace Ddrv\Mailer;

use Ddrv\Mailer\Exception\ChannelCantBeRemovedException;
use Ddrv\Mailer\Exception\ChannelIsExistsException;
use Ddrv\Mailer\Exception\ChannelNotExistsException;
use Ddrv\Mailer\Transport\Fake;
use Ddrv\Mailer\Transport\Sendmail;
use Ddrv\Mailer\Transport\TransportInterface;
use Ddrv\Mailer\Transport\Smtp;

final class Mailer
{

    const MAILER_VERSION = '3.0.0-beta';

    const CHANNEL_DEFAULT = 'default';

    const TRANSPORT_SENDMAIL = 'sendmail';
    const TRANSPORT_SMTP     = 'smtp';
    const TRANSPORT_NULL     = 'null';

    /**
     * @var TransportInterface[]
     */
    private $channels = array();

    /**
     * Mailer constructor.
     * @param string $transport
     * @param array $options
     * @throws ChannelIsExistsException
     */
    public function __construct($transport = self::TRANSPORT_SENDMAIL, $options = array())
    {
        $this->setChannel(self::CHANNEL_DEFAULT, $transport, $options);
    }

    /**
     * @param string $name
     * @param string $transport
     * @param array $options
     * @throws ChannelIsExistsException
     */
    public function setChannel($name, $transport, $options = array())
    {
        $name = (string)$name;
        if (array_key_exists($name, $this->channels)) {
            throw new ChannelIsExistsException($name);
        }
        switch ($transport) {
            case self::TRANSPORT_NULL:
                $connect = new Fake();
                break;
            case self::TRANSPORT_SMTP:
                $host = array_key_exists('host', $options)?(string)$options['host']:'';
                $port = array_key_exists('port', $options)?(string)$options['port']:'';
                $username = array_key_exists('username', $options)?(string)$options['username']:'';
                $password = array_key_exists('password', $options)?(string)$options['password']:'';
                $domain = array_key_exists('domain', $options)?(string)$options['domain']:'';
                $sender = array_key_exists('sender', $options)?(string)$options['sender']:'';
                $encrypt = array_key_exists('encrypt', $options)?(string)$options['encrypt']:null;
                $connect = new Smtp($host, $port, $username, $password, $sender, $encrypt, $domain);
                break;
            default:
                $opt = array_key_exists('options', $options)?(string)$options['options']:'';
                $sender = array_key_exists('sender', $options)?(string)$options['sender']:'';
                $connect = new Sendmail($sender, $opt);
        }
        $this->channels[$name] = $connect;
    }

    /**
     * @param string $name
     * @throws ChannelCantBeRemovedException
     */
    public function removeChannel($name)
    {
        $name = (string)$name;
        if ($name == self::CHANNEL_DEFAULT) {
            throw new ChannelCantBeRemovedException($name);
        }
        unset($this->channels[$name]);
    }

    /**
     * @param Message $message
     * @param string[] $to
     * @param string $channel
     * @throws ChannelNotExistsException
     */
    public function send(Message $message, $to, $channel = self::CHANNEL_DEFAULT)
    {
        foreach ($to as $address) {
            $this->mass($message, array($address), array(), array(), $channel);
        }
    }

    /**
     * @param Message $message
     * @param string[] $to
     * @param string[] $cc
     * @param string[] $bcc
     * @param string $channel
     * @throws ChannelNotExistsException
     */
    public function mass(Message $message, $to, $cc = array(), $bcc = array(), $channel = self::CHANNEL_DEFAULT)
    {
        $channel = (string)$channel;
        if (!array_key_exists($channel, $this->channels)) {
            throw new ChannelNotExistsException($channel);
        }
        $message->setHeader('X-Mailer', 'ddrv/mailer-'.self::MAILER_VERSION.' (https://github.com/ddrv/mailer)');
        $message->setHeader('To', implode(',', $to));
        $message->setHeader('CC', implode(',', $cc));
        $message->setHeader('BCC', implode(',', $bcc));
        $addresses = array_unique(array_merge($to, $cc, $bcc));
        $this->channels[$channel]->send($message, $addresses);
    }
}