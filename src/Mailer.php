<?php

namespace Ddrv\Mailer;

/**
 * PHP Class for sending email.
 *
 * @author   Ivan Dudarev (https://github.com/ddrv)
 * @license  MIT
 * @link     http://ddrv.ru/
 *
 * @property array    $sender
 * @property string   $subject
 * @property array    $headers
 * @property array    $body
 * @property array    $attachments
 * @property array    $address
 * @property string   $log
 * @property boolean  $smtp
 * @property resource $socket
 */
class Mailer {
    /**
     * Version of Mailer
     */
    const MAILER_VERSION = '2.1.0';

    /**
     * End of line symbol
     */
    const EOL = "\r\n";

    /**
     * Send from this Email.
     *
     * @var array
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
     * Address.
     *
     * @var array
     */
    protected $address;

    /**
     * SMTP Socket.
     *
     * @var resource
     */
    protected $socket;

    /**
     * use SMTP.
     *
     * @var boolean
     */
    protected $smtp;

    /**
     * Log.
     *
     * @var string
     */
    protected $log;

    /**
     * Mailer constructor.
     *
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Mailer destructor.
     *
     */
    public function __destruct()
    {
        if ($this->smtp) {
            $this->smtpCommand('QUIT');
            $this->socket = null;
        }
    }

    /**
     * Set SMTP.
     *
     * @param string $host
     * @param integer $port
     * @param string $user
     * @param string $password
     * @void
     */
    public function smtp($host=null, $port=null, $user=null, $password=null, $domain=null)
    {
        $this->smtp = false;
        $host = (string)$host;
        $port = (integer)$port;
        $user = (string)$user;
        $password = (string)$password;
        $domain = (string)$domain;
        if ($host && $port) {
            $this->smtp = true;
            $this->socket = fsockopen((string)$host, (int)$port, $errno, $errstr, 30);
            $test = fgets($this->socket, 512);
            unset($test);
            $this->smtpCommand('HELO '.$domain);
            $this->smtpCommand('AUTH LOGIN');
            $this->smtpCommand(base64_encode($user));
            $this->smtpCommand(base64_encode($password));
        }
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
        $senderEmail = (string)$senderEmail;
        $senderName = (string)$senderName;
        $this->sender = [
            'address' => $senderEmail,
            'name' => $senderName,
        ];
        $from = empty($senderName)?'<'.$senderEmail.'>':$senderName.' <'.$senderEmail.'>';
        $this->setHeader('From', $from, true);
        $this->setHeader('Reply-To', $from, true);
    }

    /**
     * Add address.
     *
     * @param string $addressEmail
     * @param string $addressName
     * @void
     */
    public function addAddress($addressEmail, $addressName='')
    {
        $addressEmail = (string)$addressEmail;
        $addressName = (string)$addressName;
        $this->address[$addressEmail] = $addressName;
    }

    /**
     * Remove address.
     *
     * @param string $addressEmail
     * @void
     */
    public function removeAddress($addressEmail)
    {
        if (isset($this->address[$addressEmail])) {
            unset($this->address[$addressEmail]);
        }
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
     * @void
     */
    public function send()
    {
        if (empty($this->address)) return;
        $body = empty($this->attachments)?$this->getBodySimpleText():$this->getBodyMultipart();
        $headers = implode("\r\n",$this->headers);
        if ($this->smtp) {
            $this->smtpCommand('MAIL FROM: <'.$this->sender['address'].'>');
            foreach ($this->address as $address=>$name) {
                $this->smtpCommand('RCPT TO: <'.$address.'>');
            }
            $this->smtpCommand('DATA');
            $headers = 'SUBJECT: '.$this->subject.self::EOL.$headers;
            $data = $headers.self::EOL.self::EOL.$body.self::EOL.'.';
            $this->smtpCommand($data);
        } else {
            $addresses = [];
            foreach ($this->address as $address=>$name) {
                $addresses[] = $name.' <'.$address.'>';
            }
            $list = implode(',',$addresses);
            $this->log .= '> mail(\''.$list.'\', \''.$this->subject.'\', \''.$body.'\', \''.$headers.'\');'.PHP_EOL;
            mail($list, $this->subject, $body, $headers);
        }
        $this->reset();
    }

    /**
     * Return log
     *
     * @return string
     */
    public function getLog()
    {
        return $this->log;
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
        $this->address = [];
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
        $this->setHeader('Content-Type', 'multipart/mixed; boundary="'.$separator.'"', true);
        $this->setHeader('Content-Transfer-Encoding', '7bit', true);
        $body[] = null;
        $b = self::EOL.'Content-type: text/html; charset=utf8'.self::EOL;
        $message = isset($this->body['content'])?$this->body['content']:null;
        $b .= 'Content-Transfer-Encoding: 8bit'.self::EOL;
        $b .= self::EOL.$message.self::EOL;
        $body[] = $b;
        foreach ($this->attachments as $attachment=>$data) {
            $b = self::EOL;
            if (!empty($data['headers'])) {
                foreach ($data['headers'] as $header=>$value) {
                    $b .= $header.': '.$value.self::EOL;
                }
            }
            $content = isset($data['content'])?$data['content']:null;
            $b .= self::EOL.$content.self::EOL;
            $body[] = $b;
        }
        $body[] = '--';
        return implode('--'.$separator,$body);
    }

    /**
     * Run SMTP Command
     *
     * @param $command
     * @void
     */
    protected function smtpCommand($command)
    {
        $this->log .= '> ' . $command.PHP_EOL;
        if ($this->socket) {
            fputs($this->socket, $command.self::EOL);
            $response = fgets($this->socket, 512);
            $this->log .= '< ' . $response.PHP_EOL;
        } else {
            $this->log .= '< SMTP socket undefined.'.PHP_EOL;
        }
    }
}