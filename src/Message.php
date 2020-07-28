<?php

namespace Ddrv\Mailer;

use Ddrv\Mailer\Contract\Message as MessageContract;
use Ddrv\Mailer\Exception\HeaderNotModifiedException;
use Ddrv\Mailer\Exception\InvalidAttachmentNameException;
use Ddrv\Mailer\Exception\InvalidEmailException;
use Exception;

final class Message implements MessageContract
{

    const RECIPIENT_TO = 'to';
    const RECIPIENT_CC = 'cc';
    const RECIPIENT_BCC = 'bcc';

    /**
     * @var string[]
     */
    private $headers;

    /**
     * @var array
     */
    private $recipients = array();

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $boundary;

    /**
     * @var string
     */
    private $html;

    /**
     * @var string
     */
    private $text;

    /**
     * @var array
     */
    private $attachments = array();

    /**
     * @var array
     */
    private $contents;

    /**
     * @var array
     */
    private $protectedHeaders = array(
        'subject' => array('setSubject($subject)', 'removeSubject()'),
        'from' => array('setSender($email, $name)', 'removeSender()'),
        'to' => array('addRecipient($email, $name, \'to\')', 'removeRecipients(\'to\')'),
        'cc' => array('addRecipient($email, $name, \'cc\')', 'removeRecipients(\'cc\')'),
        'bcc' => array('addRecipient($email, $name, \'bcc\')', 'removeRecipients(\'bcc\')'),
        'content-transfer-encoding' => null,
        'content-type' => null,
        'mime-version' => null,
        'message-id' => null,
    );

    /**
     * @var string[]
     */
    private $map = array(
        '=00', '=01', '=02', '=03', '=04', '=05', '=06', '=07', '=08', '=09', '=0A', '=0B', '=0C', '=0D', '=0E', '=0F',
        '=10', '=11', '=12', '=13', '=14', '=15', '=16', '=17', '=18', '=19', '=1A', '=1B', '=1C', '=1D', '=1E', '=1F',
        '=20', '=21', '=22', '=23', '=24', '=25', '=26', '=27', '=28', '=29', '=2A', '=2B', '=2C', '=2D', '=2E', '=2F',
        '=30', '=31', '=32', '=33', '=34', '=35', '=36', '=37', '=38', '=39', '=3A', '=3B', '=3C', '=3D', '=3E', '=3F',
        '=40', '=41', '=42', '=43', '=44', '=45', '=46', '=47', '=48', '=49', '=4A', '=4B', '=4C', '=4D', '=4E', '=4F',
        '=50', '=51', '=52', '=53', '=54', '=55', '=56', '=57', '=58', '=59', '=5A', '=5B', '=5C', '=5D', '=5E', '=5F',
        '=60', '=61', '=62', '=63', '=64', '=65', '=66', '=67', '=68', '=69', '=6A', '=6B', '=6C', '=6D', '=6E', '=6F',
        '=70', '=71', '=72', '=73', '=74', '=75', '=76', '=77', '=78', '=79', '=7A', '=7B', '=7C', '=7D', '=7E', '=7F',
        '=80', '=81', '=82', '=83', '=84', '=85', '=86', '=87', '=88', '=89', '=8A', '=8B', '=8C', '=8D', '=8E', '=8F',
        '=90', '=91', '=92', '=93', '=94', '=95', '=96', '=97', '=98', '=99', '=9A', '=9B', '=9C', '=9D', '=9E', '=9F',
        '=A0', '=A1', '=A2', '=A3', '=A4', '=A5', '=A6', '=A7', '=A8', '=A9', '=AA', '=AB', '=AC', '=AD', '=AE', '=AF',
        '=B0', '=B1', '=B2', '=B3', '=B4', '=B5', '=B6', '=B7', '=B8', '=B9', '=BA', '=BB', '=BC', '=BD', '=BE', '=BF',
        '=C0', '=C1', '=C2', '=C3', '=C4', '=C5', '=C6', '=C7', '=C8', '=C9', '=CA', '=CB', '=CC', '=CD', '=CE', '=CF',
        '=D0', '=D1', '=D2', '=D3', '=D4', '=D5', '=D6', '=D7', '=D8', '=D9', '=DA', '=DB', '=DC', '=DD', '=DE', '=DF',
        '=E0', '=E1', '=E2', '=E3', '=E4', '=E5', '=E6', '=E7', '=E8', '=E9', '=EA', '=EB', '=EC', '=ED', '=EE', '=EF',
        '=F0', '=F1', '=F2', '=F3', '=F4', '=F5', '=F6', '=F7', '=F8', '=F9', '=FA', '=FB', '=FC', '=FD', '=FE', '=FF',
    );

    /**
     * @var string[]
     */
    private $mime = array(
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',

        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    );

    /**
     * @param string|null $subject
     * @param string|null $html
     * @param string|null $text
     */
    public function __construct($subject = null, $html = null, $text = null)
    {
        $this->id = $this->randomString() . $this->randomString() . $this->randomString() . $this->randomString();
        $this->boundary = $this->randomString();

        $this->headers = array(
            'mime-version' => '1.0',
            'message-id' => '<' . $this->id . '>',
            'content-type' => 'text/plain; charset=UTF-8',
            'content-transfer-encoding' => 'quoted-printable',
            'x-mailer' => 'ddrv/mailer-' . Mailer::MAILER_VERSION . ' (https://github.com/ddrv/php-mailer)',
        );
        $this->setSubject($subject);
        $this->setHtml($html);
        $this->setText($text);
    }

    public function __clone()
    {
        $this->id = $this->randomString() . $this->randomString() . $this->randomString() . $this->randomString();
        $this->setAnyHeader('message-id', '<' . $this->id . '>');
        preg_match('/(?<name>.*)?<(?<email>[^>]+)>$/ui', (string)$this->getHeader('from'), $matches);
        if (array_key_exists('email', $matches)) {
            $arr = array_replace(array('', ''), explode('@', $matches['email']));
            $host = $arr[1];
            if ($host) {
                $this->setAnyHeader('message-id', '<' . $this->id . '@' . $host . '>');
            }
        }
    }

    /**
     * @param string|null $subject Subject of message.
     * @return self
     */
    public function setSubject($subject)
    {
        $subject = (string)$subject;
        $this->setAnyHeader('subject', $subject);
        return $this;
    }

    /**
     * @param string|null $html HTML text of message.
     * @return self
     */
    public function setHtml($html = null)
    {
        if (!is_null($html)) {
            $html = trim((string)$html);
            if (!$html) {
                $html = null;
            }
        }
        $this->html = $html;
        $this->contents = array();
        $this->defineContentType();
        return $this;
    }

    /**
     * @param string|null $text Plain text of message.
     * @return self
     */
    public function setText($text = null)
    {
        if (!is_null($text)) {
            $text = trim((string)$text);
            if (!$text) {
                $text = null;
            }
        }
        $this->text = $text;
        $this->defineContentType();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setSender($email, $name = null)
    {
        $email = (string)$email;
        if (!$email) {
            $this->setAnyHeader('message-id', '<' . $this->id . '>');
            return $this;
        }
        $this->checkEmail($email);
        list($null, $host) = explode('@', $email . '@');
        unset($null);
        $id = $this->id;
        if ($host) {
            $id .= '@' . $host;
        }
        $this->setAnyHeader('message-id', '<' . $id . '>');
        $contact = $this->getContact($email, $name);
        $this->setAnyHeader('from', $contact);
        return $this;
    }

    /**
     * @param string $email Recipient email.
     * @param string|null $name Recipient name.
     * @param string $type Recipient type. May be 'to', 'cc' or 'bcc'. Default 'to'.
     * @return self
     * @throws InvalidEmailException
     */
    public function addRecipient($email, $name = null, $type = self::RECIPIENT_TO)
    {
        $type = mb_strtolower((string)$type);
        if (!in_array($type, array(self::RECIPIENT_CC, self::RECIPIENT_BCC))) {
            $type = self::RECIPIENT_TO;
        }
        $email = (string)$email;
        $this->checkEmail($email);
        $this->recipients[$email] = array(
            'header' => $type,
            'name' => $name,
        );
        return $this;
    }

    /**
     * @param string $email Recipient email.
     * @return string|null Recipient name or null.
     */
    public function getRecipientName($email)
    {
        if (!array_key_exists($email, $this->recipients)) {
            return null;
        }
        return $this->recipients[$email]['name'];
    }

    /**
     * @param string $email Recipient email.
     * @return self
     */
    public function removeRecipient($email)
    {
        $email = (string)$email;
        if (array_key_exists($email, $this->recipients)) {
            unset($this->recipients[$email]);
        }
        return $this;
    }

    /**
     * @param string $type Recipient type. May be 'to', 'cc', 'bcc' or null. Default null.
     * @return self
     */
    public function removeRecipients($type = null)
    {
        $type = mb_strtolower((string)$type);
        if (!in_array($type, array(self::RECIPIENT_TO, self::RECIPIENT_CC, self::RECIPIENT_BCC))) {
            $type = '';
        }
        if (!$type) {
            $this->recipients = array();
            return $this;
        }
        foreach ($this->recipients as $email => $recipient) {
            if ($recipient['header'] === $type) {
                unset($this->recipients[$email]);
            }
        }
        return $this;
    }

    /**
     * @param string $name
     * @param string $content
     * @param string|null $mime
     * @return self
     */
    public function attachFromString($name, $content, $mime = null)
    {
        $content = (string)$content;
        $name = $this->prepareAttachmentName($name);
        if (!$mime) {
            $mime = $this->detectMimeType($content);
        }
        $this->attachments[$name] = array(
            'content' => base64_encode($content),
            'mime' => $mime,
        );
        $this->defineContentType();
        return $this;
    }

    /**
     * @param string $name
     * @param string $path
     * @param string|null $mime
     * @return self
     */
    public function attachFromFile($name, $path, $mime = null)
    {
        if (file_exists($path)) {
            $content = file_get_contents($path);
        } else {
            $content = '';
        }
        if (!$mime) {
            $mime = $this->detectMimeType($content);
        }
        if ($mime === 'application/octet-stream') {
            $mime = $this->detectMimeType($path);
        }
        return $this->attachFromString($name, $content, $mime);
    }

    /**
     * @param string $name
     * @return self
     */
    public function detach($name)
    {
        $name = $this->prepareAttachmentName($name);
        if (array_key_exists($name, $this->attachments)) {
            unset($this->attachments[$name]);
        }
        $this->defineContentType();
        return $this;
    }

    /**
     * @param string $id
     * @param string $content
     * @param string $mime
     * @return self
     */
    public function setHtmlContentFromString($id, $content, $mime = 'application/octet-stream')
    {
        $content = (string)$content;
        $id = $this->prepareContentId($id);
        if (!$mime) {
            $mime = $this->detectMimeType($content);
        }
        $this->contents[$id] = array(
            'content' => base64_encode($content),
            'mime' => $mime,
        );
        $this->defineContentType();
        return $this;
    }

    /**
     * @param string $id
     * @param string $path
     * @param string $mime
     * @return self
     */
    public function setHtmlContentFromFile($id, $path, $mime = 'application/octet-stream')
    {
        if (file_exists($path)) {
            $content = file_get_contents($path);
        } else {
            $content = '';
        }
        if (!$mime) {
            $mime = $this->detectMimeType($content);
        }
        if ($mime === 'application/octet-stream') {
            $mime = $this->detectMimeType($path);
        }
        return $this->setHtmlContentFromString($id, $content, $mime);
    }

    /**
     * @param string $id
     * @return self
     */
    public function unsetBodyHtmlContent($id)
    {
        $id = $this->prepareContentId($id);
        if (array_key_exists($id, $this->attachments)) {
            unset($this->attachments[$id]);
        }
        $this->defineContentType();
        return $this;
    }

    /**
     * @param string $header Header name.
     * @param string|null $value Header values.
     * @return self
     */
    public function setHeader($header, $value)
    {
        $value = trim((string)$value);
        if (!$value) {
            return $this->removeHeader($header);
        }
        $this->touchHeader($header, false);
        $this->setAnyHeader($header, $value);
        return $this;
    }

    /**
     * @param string $header Header name.
     * @return bool true if exists.
     */
    public function hasHeader($header)
    {
        $header = $this->prepareHeaderName($header);
        return array_key_exists($header, $this->headers);
    }

    /**
     * @param string $header Header name.
     * @return string|null Header values.
     */
    public function getHeader($header)
    {
        $header = $this->prepareHeaderName($header);
        return array_key_exists($header, $this->headers) ? $this->headers[$header] : null;
    }

    /**
     * @param string $header Header name.
     * @return string|null Header values.
     */
    public function removeHeader($header)
    {
        $this->touchHeader($header, true);
        $this->removeAnyHeader($header);
        return $this;
    }

    /**
     * @return string[] Recipients emails.
     */
    public function getRecipients()
    {
        return array_keys($this->recipients);
    }

    /**
     * @return string|null
     */
    public function getSubject()
    {
        return $this->getHeader('subject');
    }

    /**
     * @return string Rew string as email headers
     */
    public function getHeadersRaw()
    {
        $headers = array();
        foreach ($this->headers as $name => $line) {
            $header = $this->normalizeHeaderName($name);
            $values = explode(',', $line);
            foreach ($values as $value) {
                $value = trim($value);
                if (!$value) {
                    continue;
                }
                $headers[] = $this->encodeHeader($header, $value);
            }
        }
        foreach ($this->recipients as $email => $recipient) {
            if (
                !array_key_exists('header', $recipient)
                || !is_string($recipient['header'])
                || !in_array($recipient['header'], array(self::RECIPIENT_TO, self::RECIPIENT_CC, self::RECIPIENT_BCC))
            ) {
                continue;
            }
            $value = $this->getContact($email, $recipient['name']);
            $headers[] = $this->encodeHeader(ucfirst($recipient['header']), $value);
        }
        return implode("\r\n", $headers);
    }

    /**
     * @inheritDoc
     */
    public function getBodyRaw()
    {
        $info = $this->getBodyInfo(false);
        return $info['data'];
    }

    public function getPersonalMessages()
    {
        $messages = array();
        foreach ($this->getRecipients() as $recipient) {
            $name = $this->getRecipientName($recipient);
            $new = clone $this;
            $messages[] = $new->removeRecipients()->addRecipient($recipient, $name);
        }
        return $messages;
    }

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        $raw = array(
            'id' => $this->id,
            'headers' => $this->headers,
            'boundary' => $this->boundary,
            'html' => $this->html,
            'text' => $this->text,
            'attachments' => $this->attachments,
            'contents' => $this->contents,
            'recipients' => $this->recipients,
        );
        return serialize($raw);
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $raw = unserialize($serialized);
        $empty = array(
            'id' => array(),
            'headers' => array(),
            'boundary' => null,
            'html' => null,
            'text' => null,
            'attachments' => array(),
            'contents' => array(),
            'recipients' => array(),
        );
        foreach ($empty as $key => $default) {
            $this->$key = array_key_exists($key, $raw) ? $raw[$key] : $default;
        }
    }

    /**
     * @param bool $onlyType
     * @return array
     */
    private function getBodyInfo($onlyType)
    {
        $result = array(
            'type' => '',
            'data' => '',
        );
        $main = $this->getMainInfo($onlyType);
        if (empty($this->attachments)) {
            return $main;
        }
        $result['type'] = 'multipart/mixed; boundary="mail=_' . $this->boundary . '"';
        if ($onlyType) {
            return $result;
        }
        $eol = "\r\n";
        $body = $eol;
        $body .= $this->encodeHeader('Content-Type', $main['type']) . $eol;
        $body .= 'Content-Transfer-Encoding: quoted-printable' . $eol;
        $body .= $eol;
        $body .= $main['data'];
        $parts = array(null, $body);
        foreach ($this->attachments as $name => $attachment) {
            $part = $eol;
            $part .= $this->encodeHeader('Content-Type', $attachment['mime'] . '; name=' . $name) . $eol;
            $part .= $this->encodeHeader('Content-Disposition', 'attachment; filename=' . $name) . $eol;
            $part .= 'Content-Transfer-Encoding: base64' . $eol;
            $part .= $eol;
            $part .= chunk_split($attachment['content']);
            $parts[] = $part;
        }
        $parts[] = '--';
        $result['data'] = implode('--mail=_' . $this->boundary, $parts) . $eol;
        return $result;
    }

    /**
     * @param bool $onlyType
     * @return array
     */
    private function getMainInfo($onlyType)
    {
        $mixed = false;
        $result = array(
            'type' => '',
            'data' => '',
        );
        if (is_null($this->html) && is_null($this->text)) {
            return $result;
        }
        $htmlInfo = $this->getHtmlInfo($onlyType);
        if (!is_null($this->html) && is_null($this->text)) {
            return $htmlInfo;
        }
        if (!is_null($this->text) && is_null($this->html)) {
            $result['type'] = 'text/plain; charset=UTF-8';
            if (!$onlyType) {
                $result['data'] = quoted_printable_encode($this->text);
            }
            return $result;
        }
        if (!is_null($this->text) && !is_null($this->html)) {
            $result['type'] = 'multipart/alternative; boundary="body=_' . $this->boundary . '"';
            $mixed = true;
        }
        if ($onlyType) {
            return $result;
        }
        if (!$mixed) {
            $result['data'] = $htmlInfo['data'];
            return $result;
        }

        $eol = "\r\n";
        $text = $eol;
        $text .= 'Content-Type: text/plain; charset=UTF-8' . $eol;
        $text .= 'Content-Transfer-Encoding: quoted-printable' . $eol;
        $text .= $eol;
        $text .= quoted_printable_encode($this->text) . $eol;

        $html = $eol;
        $html .= $this->encodeHeader('Content-Type', $htmlInfo['type']) . $eol;
        $html .= 'Content-Transfer-Encoding: quoted-printable' . $eol;
        $html .= $eol;
        $html .= $htmlInfo['data'] . $eol;

        $parts = array(null, $text, $html, '--');
        $result['data'] = implode('--body=_' . $this->boundary, $parts) . $eol;
        return $result;
    }

    /**
     * @param bool $onlyType
     * @return array
     */
    private function getHtmlInfo($onlyType)
    {
        $mixed = false;
        $result = array(
            'type' => '',
            'data' => '',
        );
        if (is_null($this->html)) {
            return $result;
        }
        $raw = quoted_printable_encode($this->html);
        if (empty($this->contents)) {
            $result['type'] = 'text/html; charset=UTF-8';
        } else {
            $result['type'] = 'multipart/related; boundary="html=_' . $this->boundary . '"';
            $mixed = true;
        }
        if ($onlyType) {
            return $result;
        }
        if (!$mixed) {
            $result['data'] = $raw;
            return $result;
        }
        $eol = "\r\n";
        $html = $eol;
        $html .= 'Content-Type: text/html; charset=UTF-8' . $eol;
        $html .= 'Content-Transfer-Encoding: quoted-printable' . $eol;
        $html .= $eol;
        $html .= $raw . $eol;
        $parts = array(null, $html);
        foreach ($this->contents as $id => $content) {
            $part = $eol;
            $part .= $this->encodeHeader('Content-Type', $content['mime'] . '; name=' . $id) . $eol;
            $part .= 'Content-Transfer-Encoding: base64' . $eol;
            $part .= 'Content-Disposition: inline' . $eol;
            $part .= $this->encodeHeader('Content-ID', '<' . $id . '>') . $eol . $eol;
            $part .= chunk_split($content['content']);
            $parts[] = $part;
        }
        $parts[] = '--';
        $result['data'] = implode('--html=_' . $this->boundary, $parts) . $eol;
        return $result;
    }

    /**
     * @void
     */
    private function defineContentType()
    {
        $info = $this->getBodyInfo(true);
        $this->setAnyHeader('content-type', $info['type']);
    }

    /**
     * @return string
     */
    private function randomString()
    {
        $start = 268435456;
        $finish = 4294967295;
        $rand = null;
        if (function_exists('random_int')) {
            try {
                /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
                $rand = random_int($start, $finish);
            } catch (Exception $e) {
                $rand = null;
            }
        }
        if (!$rand) {
            $rand = rand($start, $finish);
        }
        return dechex($rand);
    }

    /**
     * @param string $header Header name.
     * @param string $value Header values.
     * @return self
     */
    private function setAnyHeader($header, $value)
    {
        $header = $this->prepareHeaderName($header);
        $value = $this->prepareHeaderValue($value);
        if (!$header) {
            return $this;
        }
        if ($value) {
            $this->headers[$header] = $value;
        } else {
            if (array_key_exists($header, $this->headers)) {
                unset($this->headers[$header]);
            }
        }
        return $this;
    }

    /**
     * @param string $header Header name.
     * @return string|null Header values.
     */
    private function removeAnyHeader($header)
    {
        $header = $this->prepareHeaderName($header);
        if (array_key_exists($header, $this->headers)) {
            unset($this->headers[$header]);
        }
        return $this;
    }

    /**
     * @param string $header
     * @param bool $removing
     * @return bool
     * @throws HeaderNotModifiedException
     */
    private function touchHeader($header, $removing)
    {
        $header = $this->prepareHeaderName($header);
        $removing = (bool)$removing;
        if (array_key_exists($header, $this->protectedHeaders)) {
            $key = (int)$removing;
            $method = is_array($this->protectedHeaders[$header]) ? $this->protectedHeaders[$header][$key] : null;
            throw new HeaderNotModifiedException($header, $method);
        }
        return true;
    }

    /**
     * @param string $name
     * @return string
     */
    private function normalizeHeaderName($name)
    {
        if ($name === 'mime-version') {
            return 'MIME-Version';
        }
        if ($name === 'message-id') {
            return 'Message-ID';
        }
        $name = preg_replace_callback(
            '/(^|-)[a-z]/ui',
            function ($match) {
                return strtoupper($match[0]);
            },
            $name
        );
        return $name;
    }

    /**
     * @param string $name
     * @return string
     */
    private function prepareHeaderName($name)
    {
        return mb_strtolower($this->prepareHeaderValue($name));
    }

    /**
     * @param string $name
     * @return string
     */
    private function prepareHeaderValue($name)
    {
        return trim(str_replace(array("\r", "\n"), "", (string)$name));
    }

    /**
     * @param string $email
     * @return void
     * @throws InvalidEmailException
     */
    private function checkEmail($email)
    {
        $email = (string)$email;
        list($u, $h) = explode('@', $email . '@');
        if (empty($u) || empty($h)) {
            throw new InvalidEmailException($email);
        }
        $user = trim($u);
        $host = trim($h);
        if ($user !== $u) {
            throw new InvalidEmailException($email);
        }
        if ($host !== $h) {
            throw new InvalidEmailException($email);
        }
    }

    /**
     * @param string $email
     * @param string|null $name
     * @return string
     */
    private function getContact($email, $name = '')
    {
        $email = (string)$email;
        $name = preg_replace('/[^\pL\s,.\d]/ui', '', (string)$name);
        $name = trim($name);
        if ((strpos($name, ' ') !== false || strpos($name, "\t") !== false) && $name) {
            $name = '"' . $name . '"';
        }
        if ($name) {
            $name .= ' ';
        }
        return $name . '<' . $email . '>';
    }

    /**
     * Return correct attachment name.
     *
     * @param $name
     * @throws InvalidAttachmentNameException
     * @return string
     */
    private function prepareAttachmentName($name)
    {
        $name = (string)$name;
        if (!$name || preg_match('/[\\/*?"<>\\\\]/ui', $name)) {
            throw new InvalidAttachmentNameException($name);
        }
        if (array_key_exists($name, $this->attachments)) {
            $n = 1;
            do {
                $generated = $name . ' (' . $n . ')';
                $n++;
            } while (array_key_exists($generated, $this->attachments));
            $name = $generated;
        }
        return $name;
    }

    /**
     * Return correct attachment name.
     *
     * @param $name
     * @throws InvalidAttachmentNameException
     * @return string
     */
    private function prepareContentId($name)
    {
        $name = (string)$name;
        return $name;
    }

    /**
     * @param string $header
     * @param string $value
     * @return string
     */
    private function encodeHeader($header, $value)
    {
        $max = 74;
        $offset = strlen($header) + 2;
        $symbols = str_split($value);
        unset($value);
        $result = $header . ': ';
        $coding = false;
        $all = count($symbols);
        $position = $offset;
        foreach ($symbols as $num => $symbol) {
            $line = '';
            $add = 0;
            $char = ord($symbol);
            $ascii = ($char >= 32 && $char <= 60) || ($char >= 62 && $char <= 126);
            if ($char === 32 && $num + 1 === $all) {
                $ascii = false;
            }
            if ($num < $offset) {
                $ascii = true;
                $coding = false;
            }
            if (!$coding && $char === 61 && preg_match('/;(\s+)?([a-z0-9\-]+)(\s+)?(=(\s+)?\"[^\"]+)?/ui', $result)) {
                $ascii = true;
            }
            if ($ascii) {
                if ($coding) {
                    $coding = false;
                    $line = '?=' . $symbol;
                    $add = 3;
                } else {
                    $line = $symbol;
                    $add = 1;
                }
            } else {
                if (!$coding) {
                    $coding = true;
                    $line = '=?utf-8?Q?';
                    $add = 10;
                }
                $line .= $this->map[$char];
                $add += 3;
            }
            if ($position + $add >= $max) {
                $line = "=\r\n $line";
                $position = $add + 1;
            }
            $result .= $line;
            $position += $add;
        }
        if ($coding) {
            $line = '?=';
            if ($position + 3 >= $max) {
                $line = "=\r\n $line";
            }
            $result .= $line;
        }
        return $result;
    }

    /**
     * @param string $pathOrContent Path to file or Contents
     * @return string
     */
    private function detectMimeType($pathOrContent)
    {
        $info = null;
        if (!function_exists('finfo_open')) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            $info = finfo_open(FILEINFO_MIME);
        }
        $isFile = file_exists($pathOrContent);
        if ($info) {
            if ($isFile) {
                /** @noinspection PhpComposerExtensionStubsInspection */
                $mime = finfo_file($info, $pathOrContent);
            } else {
                /** @noinspection PhpComposerExtensionStubsInspection */
                $mime = finfo_buffer($info, $pathOrContent);
            }
            /** @noinspection PhpComposerExtensionStubsInspection */
            finfo_close($info);
            return $mime;
        }
        if ($isFile) {
            $arr = explode('.', $pathOrContent);
            if ($arr) {
                $ext = strtolower(array_pop($arr));
                if (array_key_exists($ext, $this->mime)) {
                    return $this->mime[$ext];
                }
            }
        }
        return 'application/octet-stream';
    }
}
