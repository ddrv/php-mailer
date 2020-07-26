<?php

namespace Ddrv\Mailer\Contract;

use Ddrv\Mailer\Exception\InvalidEmailException;
use Serializable;

interface Message extends Serializable
{

    const RECIPIENT_TO = 'to';
    const RECIPIENT_CC = 'cc';
    const RECIPIENT_BCC = 'bcc';

    /**
     * @param string $email Recipient email.
     * @param string|null $name Recipient name.
     * @param string $type Recipient type. May be 'to', 'cc' or 'bcc'. Default 'to'.
     * @return self
     * @throws InvalidEmailException
     */
    public function addRecipient($email, $name = null, $type = self::RECIPIENT_TO);

    /**
     * @param string $email Recipient email.
     * @return string|null Recipient name or null.
     */
    public function getRecipientName($email);

    /**
     * @param string $type Recipient type. May be 'to', 'cc', 'bcc' or null. Default null.
     * @return self
     */
    public function removeRecipients($type = null);

    /**
     * @param string $email Sender email.
     * @param string|null $name Sender name.
     * @return self
     */
    public function setSender($email, $name = null);

    /**
     * @return string[] Recipients emails.
     */
    public function getRecipients();

    /**
     * @return string|null Mail subject.
     */
    public function getSubject();

    /**
     * @return string Rew string as email headers
     */
    public function getHeadersRaw();

    /**
     * @return string Raw string of email body
     */
    public function getBodyRaw();
}
