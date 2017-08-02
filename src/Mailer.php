<?php
namespace Ddrv\Mailer;
/**
 * PHP Class for sending email.
 *
 * PHP version 5.3
 *
 * @category Ddrv
 * @package  Mailer
 * @author   Ivan Dudarev (https://github.com/ddrv)
 * @license  MIT
 * @link     http://ddrv.ru/
 *
 * @property string  $sender
 * @property string  $charset
 * @property array   $headers
 */

class Mailer {
    const MAILER_VERSION = '1.0.0';

    /**
     * send from this Email
     *
     * @var string
     */
    public $sender;

    /**
     * headers of mail
     *
     * @var array
     */
    public $headers;

    /**
     * charset of message
     *
     * @var array
     */
    public $charset = 'utf8';

    /**
     * Consructor
     * @param array $sets
     */
    public function __construct($sets=array()) {
        if ($sets) {
            foreach ($sets as $property => $value) {
                if (in_array($property, array('sender','headers')) ) {
                    $this->$property = $value;
                }
            }
            $this->init();
        }
    }

    /**
     * Initialization this component and first record
     * This method requred for Yii Framevork
     */
    public function init () {
        if (empty($this->charset)) $this->charset = 'utf8';
        if (empty($this->sender)) $this->sender = 'sender@localhost';
    }

    /**
     * Send email to address
     *
     * @param string $address
     * @param string $subject
     * @param string $message
     * @param array $attachments
     */
    public function send($address,$subject,$message,$attachments=array()) {
        $rawAttachments = empty($attachments)?array():$this->getRawAttachment($attachments);
        $noModifiedHeaders = $this->headers;
        $this->setHeader('From', $this->sender, false);
        $this->setHeader('Reply-To', $this->sender, false);
        $this->setHeader('MIME-Version','1.0', false);
        $this->setHeader('X-Mailer', 'ddrvMailer-'.self::MAILER_VERSION.' (https://github.com/ddrv/mailer)', false);
        $body = empty($rawAttachments)?$this->getBodySimpleText($message):$this->getBodyMultipart($message, $rawAttachments);
        $headers = implode("\r\n",$this->headers);
        mail($address, $subject, $body, $headers);
        $this->headers = $noModifiedHeaders;
    }

    protected function getRawAttachment($attachments=array()) {
        $rawAttachments = array();
        foreach ((array)$attachments as $attachment) {
            if (
                !empty($attachment['content'])
                && !empty($attachment['name'])
                && !empty($attachment['type'])
                &&  in_array($attachment['type'], array('file','string'))
            ) {
                $name = preg_replace('/[\\\\\/\:\*\?\"<>]/ui', '_', $attachment['name']);
                $content = null;
                switch ($attachment['type']) {
                    case 'file':
                        $content = (file_exists($attachment['content']))?chunk_split(base64_encode(file_get_contents($attachment['content']))):null;
                        break;
                    case 'string':
                        $content = chunk_split(base64_encode($attachment['content']));
                        break;
                }
                if ($name && $content) {
                    $rawAttachments[$name] = $content;
                }
            }
        }
        return $rawAttachments;
    }

    protected function getBodySimpleText($message) {
        $this->setHeader('Content-type','text/html; charset='.$this->charset, true);
        return $message;
    }

    protected function getBodyMultipart($message, $attachments) {
        $separator = md5(time());
        $eol = "\r\n";
        $this->setHeader('Content-Type', 'multipart/mixed; boundary="'.$separator.'"', true);
        $this->setHeader('Content-Transfer-Encoding', '7bit', true);

        $body[] = null;
        $b = $eol.'Content-type: text/html; charset='.$this->charset.$eol;
        $bits = '7bit';
        switch ($this->charset) {
            case 'utf-8':
            case 'utf8': $bits='8bit'; break;
        }
        $b .= 'Content-Transfer-Encoding: '.$bits.$eol;
        $b .= $eol.$message.$eol;
        $body[] = $b;
        foreach ($attachments as $file=>$content) {
            $b = $eol.'Content-Type: application/octet-stream; name="' . $file . '"' . $eol;
            $b .= 'Content-Transfer-Encoding: base64' . $eol;
            $b .= 'Content-Disposition: attachment' . $eol;
            $b .= $eol.$content.$eol;
            $body[] = $b;
        }
        $body[] = '--';
        return implode('--'.$separator,$body);
    }

    public function setHeaderFrom($address) {
        $this->setHeader('From',$address);
    }

    protected function setHeader($header, $value=null, $replace=true) {
        if (!$replace && !empty($this->headers[mb_strtolower($header)])) return;
        if (($value === null || $value === false) && !empty($this->headers[mb_strtolower($header)])) {
            unset($this->headers[mb_strtolower($header)]);
        } else {
            $this->headers[mb_strtolower($header)] = $header . ': ' . $value;
        }
    }
}