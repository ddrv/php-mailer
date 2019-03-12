<?php

namespace Ddrv\Mailer;

use Iterator;

final class Book implements Iterator
{

    /**
     * @var Address[]
     */
    private $book = array();

    /**
     * @var string[]
     */
    private $exists = array();

    /**
     * @var int
     */
    private $cursor = 0;

    /**
     * @param Address $address
     * @return bool
     */
    public function add(Address $address)
    {
        if (!$address->isValid()) return false;
        $this->book[] = $address;
        $this->exists = $address->getEmail();
        return true;
    }

    /**
     * @param Address $address
     * @return bool|int
     */
    public function remove(Address $address)
    {
        if (!$address->isValid()) return false;
        $removed = 0;
        foreach ($this->book as $key => $item) {
            if ($address->getEmail() == $item->getEmail()) {
                $removed++;
                unset($this->book[$key]);
            }
        }
        $this->book = array_values($this->book);
        return $removed;
    }

    /**
     * @return string
     */
    public function getContacts()
    {
        $list = array();
        foreach ($this->book as $address) {
            $list[] = $address->getContact();
        }
        return implode(', ', $list);
    }

    /**
     * @return string
     */
    public function getEmails()
    {
        $list = array();
        foreach ($this->book as $address) {
            $list[] = $address->getEmail();
        }
        return implode(', ', $list);
    }

    public function isEmpty()
    {
        return empty($this->book);
    }

    /**
     * @return Address|null
     */
    public function current()
    {
        if (!array_key_exists($this->cursor, $this->book)) return null;
        return $this->book[$this->cursor];
    }

    /**
     * @void
     */
    public function next()
    {
        $this->cursor++;
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->cursor;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return array_key_exists($this->cursor, $this->book);
    }

    /**
     * @void
     */
    public function rewind()
    {
        $this->cursor = 0;
    }
}