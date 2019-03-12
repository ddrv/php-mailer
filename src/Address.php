<?php

namespace Ddrv\Mailer;

final class Address
{

    private $email;

    private $name;

    public function __construct($email, $name = '')
    {
        $this->email = (string)$email;
        $this->name = (string)$name;
        if (preg_match('/[^\pL\s]/', $this->name)) $this->name = "\"{$this->name}\"";
    }

    public function getContact()
    {
        if (!$this->isValid()) return null;
        if (!$this->name) return "<{$this->email}>";
        return "{$this->name} <{$this->email}>";
    }

    public function getEmail()
    {
        if (!$this->isValid()) return null;
        return $this->email;
    }

    public function isValid()
    {
        $arr = explode('@', $this->email);
        return (count($arr) == 2);
    }
}
