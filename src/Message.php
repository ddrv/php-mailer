<?php

namespace Ddrv\Mailer;

final class Message
{

    /**
     * @var string
     */
    private $subject;

    /**
     * @var array
     */
    private $message = array();

    /**
     * @var array
     */
    private $headers = array();

    /**
     * @var array
     */
    private $attachments = array();

    /**
     * @var string
     */
    private $boundary;

    public function __construct($from, $subject, $message, $isHtml = true)
    {
        $this->setSender($from);
        $this->subject = (string)$subject;
        $this->message = array(
            'content' => (string)$message,
            'mime' => $isHtml ? 'text/html' : 'text/plain',
        );
        $this->boundary = md5(time());
    }

    public function setHeader($header, $value)
    {
        $header = (string)$header;
        $this->headers[mb_strtolower($header)] = $header . ': ' . (string)$value;
        return $this;
    }

    public function removeHeader($header)
    {
        $header = mb_strtolower((string)$header);
        if (array_key_exists($header, $this->headers)) {
            unset($this->headers[$header]);
        }
        return $this;
    }

    public function attachFromString($name, $content, $mime = 'application/octet-stream')
    {
        $name = $this->prepareAttachmentName($name);
        $this->attachments[$name] = array(
            'content' => base64_encode($content),
            'mime' => $mime,
        );
        return $this;
    }

    /**
     * @param $name
     * @param $path
     * @return $this
     */
    public function attachFromFile($name, $path)
    {
        $name = $this->prepareAttachmentName($name);
        $stream = fopen($path, 'r');
        $content = '';
        if ($stream) {
            while (!feof($stream)) {
                $content .= fgets($stream, 4096);
            }
            fclose($stream);
        }
        if (!$content) {
            return $this->detach($name);
        }
        $mime = 'application/octet-stream';
        if (function_exists('mime_content_type')) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            $mime = mime_content_type($path);
        }
        $this->attachments[$name] = array(
            'content' => base64_encode($content),
            'mime' => $mime,
        );
        return $this;
    }

    public function detach($name)
    {
        $name = $this->prepareAttachmentName($name);
        if (array_key_exists($name, $this->attachments)) {
            unset($this->attachments[$name]);
        }
        return $this;
    }

    /**
     * Set sender.
     *
     * @param string $email
     * @param string $name
     * @void
     */
    public function setSender($email, $name='')
    {
        $email = (string)$email;
        $name = trim((string)$name);
        $from = '';
        if ($name) $from .= $name.' ';
        $from .= "<$email>";
        $this->setHeader('From', $from);
        $this->setHeader('Reply-To', $from);
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getHeadersLine()
    {
        if (empty($this->attachments)) {
            $this->setHeader('Content-Type', $this->message['mime'] . '; charset=utf8');
            $this->setHeader('Content-Transfer-Encoding', '8bit');
        } else {
            $this->setHeader('Content-Type', 'multipart/mixed; boundary="'.$this->boundary.'"');
            $this->setHeader('Content-Transfer-Encoding', '7bit');
        }
        $headers = implode("\r\n", $this->headers);
        return $headers;
    }

    public function getBody()
    {
        if (empty($this->attachments)) {
            $body = $this->message['content'];
        } else {
            $parts[] = null;
            $part = "\r\nContent-type: text/html; charset=utf8\r\n";
            $part .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $part .= "{$this->message['content']}\r\n";
            $parts[] = $part;
            foreach ($this->attachments as $name => $attachment) {

                $part = "\r\nContent-type: {$attachment['mime']}; name=$name\r\n";
                $part .= "Content-Transfer-Encoding: base64\r\n";
                $part .= "Content-Disposition: attachment\r\n\r\n";
                $part .= chunk_split($this->message['content'])."\r\n";
                $parts[] = $part;
            }
            $parts[] = '--';
            $body = implode('--'.$this->boundary, $parts);
        }
        return $body;
    }



    /**
     * Return correct attachment name.
     *
     * @param $name
     * @return string
     */
    private function prepareAttachmentName($name)
    {
        $name = (string)$name;
        $name = preg_replace('/[\\\\\/\:\*\?\"<>]/ui', '_', $name);
        if (!$name) {
            $n = 1;
            do {
                $generated = 'attachment_'.$n;
                $n++;
            } while (array_key_exists($generated, $this->attachments));
            $name = $generated;
        }
        return $name;
    }

}