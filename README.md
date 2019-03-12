[![Latest Stable Version](https://img.shields.io/packagist/v/ddrv/mailer.svg?style=flat-square)](https://packagist.org/packages/ddrv/mailer)
[![Total Downloads](https://img.shields.io/packagist/dt/ddrv/mailer.svg?style=flat-square)](https://packagist.org/packages/ddrv/mailer/stats)
[![License](https://img.shields.io/packagist/l/ddrv/mailer.svg?style=flat-square)](https://github.com/ddrv/mailer/blob/master/LICENSE)
[![PHP](https://img.shields.io/packagist/php-v/ddrv/mailer.svg?style=flat-square)](https://php.net)


# Mailer
PHP Class for sending email.

# Install
## With [Composer](https://getcomposer.org/)
1. Run in console:
    ```text
    php composer.phar require ddrv/mailer:~3
    ```
1. Include autoload file
    ```php
    require_once('vendor/autoload.php');
    ```

# Usage

```php
<?php

/*
 * Initialization Mailer class. 
 */
$mailer = new \Ddrv\Mailer\Mailer();

/*
 * If need use SMTP server, setting it
 */
$mailer->smtp(
    'smtp.host.name',    // host
    25,                  // port
    'from@host.name',    // login
    'password for from', // password
    'from@host.name',    // sender
    null,                // encryption: 'tls', 'ssl' or null
    'http://host.name'   // domain
);

/*
 * If need switch provider back to mail() function, use 
 */
$mailer->legacy('-f');

/*
 * Create message
 */
$message = new \Ddrv\Mailer\Message('from@host.name', 'subject', '<p>Simple text</p>', true);

/*
 * You can set named sender from@host.name as Site Administrator
 */
$message->setSender('from@host.name', 'Site Administrator');

/*
 * If need adding attachment from string, run
 */
$message->attachFromString('attach1.txt', 'content', 'text/plain');

/*
 * If need adding attachment from file, run
 */
$message->attachFromFile('attach2.txt', '/path/to/file');

/*
 * Send email to addresses (one mail for all addresses)
 */
$mailer->send($message, array('email1@host.name', 'email2@host.name'));

/*
 * or send personal mailing one mail per addresses
 */
$mailer->send($message, array('email1@host.name', 'email2@host.name', true));
```