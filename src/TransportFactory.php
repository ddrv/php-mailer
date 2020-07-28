<?php

namespace Ddrv\Mailer;

use Ddrv\Mailer\Contract\Transport;
use Ddrv\Mailer\Transport\FakeTransport;
use Ddrv\Mailer\Transport\FileTransport;
use Ddrv\Mailer\Transport\SendmailTransport;
use Ddrv\Mailer\Transport\SmtpTransport;
use InvalidArgumentException;

final class TransportFactory
{

    /**
     * @param string $transportUrl
     * @return Transport
     * @throws InvalidArgumentException
     */
    public static function make($transportUrl)
    {
        $transportUrl = preg_replace('#^(file):///#', '$1://localhost/', $transportUrl);
        $url = array_fill_keys(array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query'), '');
        $url = array_replace($url, parse_url($transportUrl));
        $path = $url['path'];
        parse_str(array_key_exists('query', $url) ? $url['query'] : '', $query);
        $sender = array_key_exists('sender', $query) ? $query['sender'] : $url['user'];
        switch ($url['scheme']) {
            case 'smtp':
                $host = (string)$url['host'];
                $port = (int)$url['port'];
                $user = urldecode($url['user']);
                $password = urldecode($url['pass']);
                $defaultEncryption = SmtpTransport::ENCRYPTION_TLS;
                $encryption = array_key_exists('encryption', $query) ? $query['encryption'] : $defaultEncryption;
                $domain = array_key_exists('domain', $query) ? $query['domain'] : '';
                $transport = new SmtpTransport($host, $port, $user, $password, $sender, $encryption, $domain);
                break;
            case 'sendmail':
                $options = array_key_exists('options', $query) ? $query['options'] : null;
                $transport = new SendmailTransport($options);
                break;
            case 'file':
                if ($path) {
                    $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
                    $path = mb_substr($path, 1);
                }
                $transport = new FileTransport($path);
                break;
            default:
                $transport = new FakeTransport();
        }
        return $transport;
    }
}
