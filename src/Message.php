<?php

namespace Ddrv\Mailer;

use Ddrv\Mailer\Contract\Message as MessageContract;
use Ddrv\Mailer\Exception\HeaderNotModifiedException;
use Ddrv\Mailer\Exception\InvalidAttachmentNameException;
use Ddrv\Mailer\Exception\InvalidEmailException;
use Exception;

final class Message implements MessageContract
{

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
        $sender = $this->getHeader('sender');
        preg_match('/(?<name>.*)?<(?<email>[^>]+)>$/ui', $sender, $matches);
        if (array_key_exists('email', $matches)) {
            $email = $matches['email'];
            $name = array_key_exists('name', $matches) ? $matches['name'] : '';
            $this->setSender($email, $name);
        }
    }

    /**
     * @param string|null $subject Subject of message.
     * @return self
     */
    public function setSubject($subject)
    {
        $subject = (string)$subject;
        if ($subject) {
            $this->setAnyHeader('subject', $subject);
        } else {
            $this->removeAnyHeader('subject');
        }
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
     * @param string $email Sender email.
     * @param string|null $name Sender name.
     * @return self
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
     * @return self
     */
    public function removeSender()
    {
        $this->removeAnyHeader('from');
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
            $mime = ddrv_mailer_define_mime_type($content);
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
            $mime = ddrv_mailer_define_mime_type($content);
        }
        if ($mime === 'application/octet-stream') {
            $mime = ddrv_mailer_define_mime_type($path);
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
            $mime = ddrv_mailer_define_mime_type($content);
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
            $mime = ddrv_mailer_define_mime_type($content);
        }
        if ($mime === 'application/octet-stream') {
            $mime = ddrv_mailer_define_mime_type($path);
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
     * @param string $value Header values.
     * @return self
     * @throws HeaderNotModifiedException
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
     * @throws HeaderNotModifiedException
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
     * @return string Raw string of email body
     */
    public function getBodyRaw()
    {
        $info = $this->getBodyInfo(false);
        return $info['data'];
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
        try {
            $rand = random_int(268435456, 4294967295);
        } catch (Exception $e) {
            $rand = rand(268435456, 4294967295);
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
            $this->removeAnyHeader($header);
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
        $string = $header . ': ' . $value;
//        if ($header === 'Content-Type' && strlen($string) <= 74) {
//            return $string;
//        }
        $len = strlen($header) + 2;
        return ddrv_mailer_encode_mime_header($string, $len);
    }
}
