<?php

namespace Ddrv\Mailer\Message;

final class MessagePart
{

    private $content;

    private $mime;

    public function __construct($content, $mimeType)
    {
        $this->content = $content;
        $this->mime = $mimeType;
    }

    public function getRaw()
    {

    }
}