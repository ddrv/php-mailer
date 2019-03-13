<?php

namespace Ddrv\Mailer;

use Ddrv\Mailer\Exception\ChannelCantBeRemovedException;
use Ddrv\Mailer\Exception\ChannelIsExistsException;
use Ddrv\Mailer\Exception\RecipientsListEmptyException;
use Ddrv\Mailer\Transport\TransportInterface;

final class Mailer
{

    const MAILER_VERSION = "3.0.0-beta";

    const CHANNEL_DEFAULT = "default";
    const CHANNEL_ALL     = "*";

    /**
     * @var TransportInterface[]
     */
    private $channels = array();

    private $contacts = array();

    /**
     * Mailer constructor.
     * @param TransportInterface $transport
     * @param string $name
     * @throws ChannelIsExistsException
     */
    public function __construct(TransportInterface $transport, $name = self::CHANNEL_DEFAULT)
    {
        $this->setChannel($transport, $name);
    }

    /**
     * @param TransportInterface $transport
     * @param string $name
     * @throws ChannelIsExistsException
     */
    public function setChannel(TransportInterface $transport, $name)
    {
        $name = (string)$name;
        if (array_key_exists($name, $this->channels) || $name == self::CHANNEL_ALL) {
            throw new ChannelIsExistsException($name);
        }
        $this->channels[$name] = $transport;
    }

    /**
     * @param string $name
     * @throws ChannelCantBeRemovedException
     */
    public function removeChannel($name)
    {
        $name = (string)$name;
        if (in_array($name, array(self::CHANNEL_DEFAULT, self::CHANNEL_ALL))) {
            throw new ChannelCantBeRemovedException($name);
        }
        unset($this->channels[$name]);
    }

    /**
     * @param Message $message
     * @param string[] $to
     * @param string[]|string $channels
     * @throws RecipientsListEmptyException
     */
    public function send(Message $message, $to, $channels = self::CHANNEL_ALL)
    {
        foreach ($to as $address) {
            $this->mass($message, array($address), array(), array(), $channels);
        }
    }

    /**
     * @param Message $message
     * @param string[] $to
     * @param string[] $cc
     * @param string[] $bcc
     * @param string[]|string $channels
     * @throws RecipientsListEmptyException
     */
    public function mass(Message $message, $to, $cc = array(), $bcc = array(), $channels = self::CHANNEL_ALL)
    {
        $addresses = $this->getEmails(array_merge($to, $cc, $bcc));
        if (!$addresses) {
            throw new RecipientsListEmptyException();
        }
        $version = self::MAILER_VERSION;
        $message->setHeader("X-Mailer", "ddrv/mailer-$version (https://github.com/ddrv/mailer)");
        $message->setHeader("To", implode(", ", $this->getContacts($to)));
        $message->setHeader("Cc", implode(", ", $this->getContacts($cc)));
        $message->setHeader("Bcc", implode(", ", $this->getContacts($bcc)));

        $ch = $this->getChannels($channels);
        foreach ($ch as $transport) {
            $sender = $transport->getSender();
            $contact = $this->getContact($sender);
            $message->setHeader("From", $contact);
            $transport->send($message, $addresses);
        }
    }

    public function addContact($email, $name)
    {
        $email = (string)$email;
        $name = preg_replace("/[^\pL\s\,\.\d]/ui", "", (string)$name);
        if (!$email || !$name) return false;
        if (!$this->checkEmail($email)) return false;
        if (preg_match("/[\,\.]/ui", $name)) $name = "\"$name\"";
        $this->contacts[$email] = "$name <$email>";
        return true;
    }

    public function clearContacts()
    {
        $this->contacts = array();
    }

    /**
     * @param callable $logger
     * @param string[]|string $channels
     */
    public function setLogger(callable $logger, $channels = self::CHANNEL_ALL)
    {
        $ch = $this->getChannels($channels);
        foreach ($ch as $transport) {
            $transport->setLogger($logger);
        }
    }

    private function checkEmail($email)
    {
        $arr = explode("@", $email);
        return (count($arr) == 2 && !empty($arr[0]) && !empty($arr[1]));
    }

    private function getEmails($emails)
    {
        $emails = array_unique($emails);
        $result = array();
        foreach ($emails as $email) {
            if ($this->checkEmail($email)) {
                $result[] = $email;
            }
        }
        return $result;
    }

    private function getContacts($emails)
    {
        $emails = array_unique($emails);
        $result = array();
        foreach ($emails as $email) {
            $contact = $this->getContact($email);
            if ($contact) $result[] = $contact;
        }
        return $result;
    }

    private function getContact($email)
    {
        if (array_key_exists($email, $this->contacts)) {
            return $this->contacts[$email];
        } elseif ($this->checkEmail($email)) {
            return "<$email>";
        }
        return false;
    }

    /**
     * @param string|array $channels
     * @return TransportInterface[]
     */
    private function getChannels($channels)
    {
        $result = array();
        $all = (
            (is_string($channels) && $channels == self::CHANNEL_ALL)
            || (is_array($channels) && in_array(self::CHANNEL_ALL, $channels))
        );
        if ($all) {
            $ch = array_keys($this->channels);
        } else {
            $ch = (array)$channels;
        }
        foreach ($ch as $channel) {
            if (array_key_exists($channel, $this->channels)) {
                $result[$channel] = $this->channels[$channel];
            }
        }
        return array_values($result);
    }


}