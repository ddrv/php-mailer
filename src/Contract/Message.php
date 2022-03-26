<?php

namespace Ddrv\Mailer\Contract;

use Serializable;

interface Message extends Serializable
{
    /**
     * @param string $email Sender email.
     * @param string|null $name Sender name.
     * @return $this
     */
    public function setSender($email, $name = null);

    /**
     * @return string[] Recipients emails.
     */
    public function getRecipients();

    /**
     * @return string Raw string as email headers
     */
    public function getHeadersRaw();

    /**
     * @return string Raw string of email body
     */
    public function getBodyRaw();

    /**
     * @return Message[]
     */
    public function getPersonalMessages();
}
