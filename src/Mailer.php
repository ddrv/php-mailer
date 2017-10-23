<?php

namespace Ddrv\Mailer;

/**
 * PHP Class for sending email.
 *
 * @author   Ivan Dudarev (https://github.com/ddrv)
 * @license  MIT
 * @link     http://ddrv.ru/
 *
 * @property string  $sender
 * @property string  $subject
 * @property array   $headers
 * @property array   $body
 * @property array   $attachments
 */
class Mailer {
    /**
     * Version of Mailer
     */
    const MAILER_VERSION = '2.0.0';

    /**
     * Send from this Email.
     *
     * @var string
     */
    protected $sender;

    /**
     * Subject of mail.
     *
     * @var string
     */
    protected $subject;

    /**
     * Headers of mail.
     *
     * @var array
     */
    protected $headers;

    /**
     * Body of mail.
     *
     * @var array
     */
    protected $body;

    /**
     * Attachments.
     *
     * @var array
     */
    protected $attachments;

    /**
     * Mailer constructor.
     *
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Set sender.
     *
     * @param string $senderEmail
     * @param string $senderName
     * @void
     */
    public function sender($senderEmail, $senderName='')
    {
        $this->sender = (string)$senderEmail;
        $senderName = (string)$senderName;
        if ($senderName) {
            $this->sender .= '<'.$senderName.'>';
        }
        $this->reset();
    }

    /**
     * Set subject of message.
     *
     * @param string $subject
     * @void
     */
    public function subject($subject)
    {
        $this->subject = (string)$subject;
    }

    /**
     * Set body of message.
     *
     * @param string $text
     * @void
     */
    public function body($text)
    {
        $this->body = [
            'headers' => [
                'Content-Type' => 'text/html; charset=utf8',
            ],
            'content' => (string)$text,
        ];
    }

    /**
     * Add attachment from string.
     *
     * @param $content
     * @param $attachmentName
     * @void
     */
    public function attachFromString($content, $attachmentName)
    {
        $content = (string)$content;
        if (!$content) {
            return;
        }
        $attachmentName = $this->prepareAttachmentName($attachmentName);
        $this->attachments[$attachmentName] = [
            'headers' => [
                'Content-Type'              => 'application/octet-stream; name="'.$attachmentName.'"',
                'Content-Transfer-Encoding' => 'base64',
                'Content-Disposition'       => 'attachment',
            ],
            'content' => chunk_split(base64_encode($content)),
        ];
    }

    /**
     * Add attachment file.
     *
     * @param $file
     * @param $attachmentName
     * @void
     */
    public function attachFromFile($file, $attachmentName)
    {
        $file = (string)$file;
        if (!is_readable($file)) {
            return;
        }
        $attachmentName = $this->prepareAttachmentName($attachmentName);
        $this->attachments[$attachmentName] = [
            'headers' => [
                'Content-Type'              => 'application/octet-stream; name="'.$attachmentName.'"',
                'Content-Transfer-Encoding' => 'base64',
                'Content-Disposition'       => 'attachment',
            ],
            'content' => chunk_split(base64_encode(file_get_contents($file))),
        ];
    }

    /**
     * Send message.
     *
     * @param string $address
     * @void
     */
    public function send($address)
    {
        $body = empty($this->attachments)?$this->getBodySimpleText():$this->getBodyMultipart();
        $headers = implode("\r\n",$this->headers);
        mail($address, $this->subject, $body, $headers);
        $this->reset();
    }

    /**
     * Reset body and headers for nex mail
     * @void
     */
    protected function reset()
    {
        $this->subject = '';
        $this->body = [];
        $this->headers = [];
        $this->attachments = [];
        $this->setHeader('From', $this->sender, false);
        $this->setHeader('Reply-To', $this->sender, false);
        $this->setHeader('MIME-Version','1.0', false);
        $this->setHeader('X-Mailer', 'Mailer-'.self::MAILER_VERSION.' (https://github.com/ddrv/mailer)', false);
    }

    /**
     * Set header.
     *
     * @param string $header
     * @param string $value
     * @param bool $replace
     * @void
     */
    protected function setHeader($header, $value='', $replace=true)
    {
        if (!$replace && !empty($this->headers[mb_strtolower($header)])) return;
        if (($value === null || $value === false) && !empty($this->headers[mb_strtolower($header)])) {
            unset($this->headers[mb_strtolower($header)]);
        } else {
            $this->headers[mb_strtolower($header)] = $header . ': ' . $value;
        }
    }

    /**
     * Return correct attachment name.
     *
     * @param $attachmentName
     * @return string
     */
    protected function prepareAttachmentName($attachmentName)
    {
        $attachmentName = (string)$attachmentName;
        $attachmentName = preg_replace('/[\\\\\/\:\*\?\"<>]/ui', '_', $attachmentName);
        if (!$attachmentName) {
            $attachmentName = 'attachment_'.(count($this->attachments)+1);
        }
        return $attachmentName;
    }

    /**
     * Set headers of body and return compiled simple body string.
     *
     * @return string
     */
    protected function getBodySimpleText()
    {
        if (!empty($this->body['headers'])) {
            foreach ($this->body['headers'] as $header => $value) {
                $this->setHeader($header, $value, true);
            }
        }
        $this->setHeader('Content-type','text/html; charset=utf8', true);
        return isset($this->body['content'])?(string)$this->body['content']:'';
    }

    /**
     * Set headers of body and return compiled multipart body string.
     *
     * @return string
     */
    protected function getBodyMultipart()
    {
        $separator = md5(time());
        $eol = "\r\n";
        $this->setHeader('Content-Type', 'multipart/mixed; boundary="'.$separator.'"', true);
        $this->setHeader('Content-Transfer-Encoding', '7bit', true);
        $body[] = null;
        $b = $eol.'Content-type: text/html; charset=utf8'.$eol;
        $message = isset($this->body['content'])?$this->body['content']:null;
        $b .= 'Content-Transfer-Encoding: 8bit'.$eol;
        $b .= $eol.$message.$eol;
        $body[] = $b;
        foreach ($this->attachments as $attachment=>$data) {
            $b = $eol;
            if (!empty($data['headers'])) {
                foreach ($data['headers'] as $header=>$value) {
                    $b .= $header.': '.$value.$eol;
                }
            }
            $content = isset($data['content'])?$data['content']:null;
            $b .= $eol.$content.$eol;
            $body[] = $b;
        }
        $body[] = '--';
        return implode('--'.$separator,$body);
    }
}