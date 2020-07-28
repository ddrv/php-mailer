[![Latest Stable Version](https://img.shields.io/packagist/v/ddrv/mailer.svg?style=flat-square)](https://packagist.org/packages/ddrv/mailer)
[![Total Downloads](https://img.shields.io/packagist/dt/ddrv/mailer.svg?style=flat-square)](https://packagist.org/packages/ddrv/mailer/stats)
[![License](https://img.shields.io/packagist/l/ddrv/mailer.svg?style=flat-square)](https://github.com/ddrv/php-mailer/blob/master/LICENSE)
[![PHP](https://img.shields.io/packagist/php-v/ddrv/mailer.svg?style=flat-square)](https://php.net)

# Mailer

PHP library for sending email.

# Install

1. Run in console:
    ```bash
    composer require ddrv/mailer:^5
    ```

1. Include autoload file
    ```php
    require_once('vendor/autoload.php');
    ```

# Usage

## Creating transport instance

### Sendmail

```php
<?php

$transport = new Ddrv\Mailer\Transport\SendmailTransport(
    '-f' // sendmail options
);
```

### SMTP

```php
<?php

$transport = new Ddrv\Mailer\Transport\SmtpTransport(
    'smtp.fight.club',  // host (REQUIRED)
    25,                 // port (REQUIRED)
    'info',             // login (REQUIRED)
    'super-secret',     // password (REQUIRED)
    'joe@fight.club',   // sender email (REQUIRED)
    null,               // encryption: 'tls', 'ssl' or null
    'http://fight.club' // domain
);
```

### Fake (emulation send emails)

```php
<?php

$transport = new Ddrv\Mailer\Transport\FakeTransport();
```

### Custom

You can implement Ddrv\Mailer\Contract\Transport interface

```php
<?php

/** @var Ddrv\Mailer\Contract\Transport $transport */
```

## Creating mailer instance

```php
<?php

/** @var Ddrv\Mailer\Contract\Transport $transport */
$mailer = new Ddrv\Mailer\Mailer($transport);
```

## Creating message instance

```php
<?php

$message = new Ddrv\Mailer\Message(
    'Subject',     // Subject
    '<p>HTML</p>', // HTML body
    'Simple text.' // Text plain body
);
```

## Sending message

```php
<?php

/**
 * @var Ddrv\Mailer\Contract\Message $message
 * @var Ddrv\Mailer\Mailer $mailer
 */
$mailer->send($message);
```

## Message methods

```php
<?php

/** @var Ddrv\Mailer\Message $message */
$message->setSubject('Welcome to Fight Club!');
$message->setText('Welcome to Fight Club!' . PHP_EOL . 'Please, read our rules in attachments.');
$html = '<h1>Welcome to Fight Club!</h1><p>Please, read our rules in attachments.</p>';
$html .= '<img src="cid:poster" alt="Poster"/><img src="cid:ticket" alt="Your ticket"/>';
$message->setHtml($html);

$message->setSender('support@fight.club', 'Support od Fight Club'); // The SMTP transport will set its value.

$message->addRecipient('tyler@fight.club', 'Tyler Durden', Ddrv\Mailer\Message::RECIPIENT_TO);
$message->addRecipient('bob@fight.club', 'Robert Paulson', Ddrv\Mailer\Message::RECIPIENT_CC);
$message->addRecipient('angel@fight.club', 'Angel Face', Ddrv\Mailer\Message::RECIPIENT_BCC);
$message->addRecipient('r.chesler@car-vendor.com', 'Richard Chesler', Ddrv\Mailer\Message::RECIPIENT_TO);

$message->getRecipientName('bob@fight.club'); // Returns 'Robert Paulson'.
$message->getRecipientName('unknown@fight.club'); // Returns null.

$message->removeRecipient('r.chesler@car-vendor.com'); // If you change your mind.

// You may remove recipients by type
$message->removeRecipients(Ddrv\Mailer\Message::RECIPIENT_TO);
$message->removeRecipients(Ddrv\Mailer\Message::RECIPIENT_CC);
$message->removeRecipients(Ddrv\Mailer\Message::RECIPIENT_BCC);
// Or all
$message->removeRecipients();

$message->addRecipient('tyler@fight.club', 'Tyler Durden', Ddrv\Mailer\Message::RECIPIENT_TO);
$message->addRecipient('bob@fight.club', 'Robert Paulson', Ddrv\Mailer\Message::RECIPIENT_CC);
$message->addRecipient('angel@fight.club', 'Angel Face', Ddrv\Mailer\Message::RECIPIENT_BCC);



$rules = <<<TEXT
1. You don't talk about fight club.
2. You don't talk about fight club.
3. When someone says stop, or goes limp, the fight is over.
4. Only two guys to a fight.
5. One fight at a time.
6. They fight without shirts or shoes.
7. The fights go on as long as they have to.
8. If this is your first night at fight club, you have to fight.
TEXT;
$message->attachFromString(
    'rules.txt',                // Attachment name (REQUIRED)
    $rules,                     // Contents (REQUIRED)
    'text/plain; charset=UTF-8' // Mime Type
);


$path = '/home/tyler/docs/projects/mayhem/rules.txt';

$message->attachFromFile(
    'project-mayhem.txt',       // Attachment name (REQUIRED)
     $path,                     // Path to attached file (REQUIRED)
    'text/plain; charset=UTF-8' // Mime Type
);

$message->detach('project-mayhem.txt'); // If you change your mind.

$message->setHtmlContentFromString(
    'ticket',                                            // HTML Content ID
    file_get_contents('/home/tyler/tickets/038994.jpg'), // Contents (REQUIRED)
    'image/jpeg'                                         // Mime Type
);

$message->setHtmlContentFromString(
    'script',                // HTML Content ID
    'alert("ok");',          // Contents (REQUIRED)
    'application/javascript' // Mime Type
);

$message->setHtmlContentFromFile(
    'poster',                            // HTML Content ID
    '/home/tyler/images/fight-club.jpg', // Path to file (REQUIRED)
    'image/jpeg'                         // Mime Type
);

$message->unsetBodyHtmlContent('script'); // If you change your mind.

$message->hasHeader('X-Some-Header');          // Returns false.
$message->setHeader('X-Some-Header', 'Value'); // Header set.
$message->hasHeader('X-Some-Header');          // Returns true.
$message->getHeader('X-Some-Header');          // Returns 'Value'.
$message->removeHeader('X-Some-Header');       // Header removed.
$message->hasHeader('X-Some-Header');          // Returns false.

$message->getRecipients(); // Returns array if recipients emails.
$message->getSubject(); // Returns mail subject.
$message->getHeadersRaw(); // Returns string of mail headers.
$message->getBodyRaw(); // Returns string of mail body.

```

You can implement Ddrv\Mailer\Contract\Message interface for work with custom messages. 

## Spool

This package allows you to use mail spool.

This requires:
* create a spool object (instance of `Ddrv\Mailer\Contract\Spool`)
* create a transport object (instance of `Ddrv\Mailer\Contract\Transport`)
* wrap them up in special transport `Ddrv\Mailer\Transport\SpoolTransport`

```php
<?php

/**
 * @var Ddrv\Mailer\Contract\Transport $transport
 * @var Ddrv\Mailer\Contract\Message $message1
 * @var Ddrv\Mailer\Contract\Message $message2
 */
$spool = new Ddrv\Mailer\Spool\MemorySpool();
// or
$spool = new Ddrv\Mailer\Spool\FileSpool('/path/to/emails');
// Or any implementation of Ddrv\Mailer\Contract\Spool

$wrapper = new Ddrv\Mailer\Transport\SpoolTransport($transport, $spool);
$mailer = new Ddrv\Mailer\Mailer($wrapper);

$mailer->send($message1);
$mailer->send($message2);

// Now the letters will only be added to the queue.
// To send them, you need to execute:

$wrapper->flush(
    100, // Number of emails sent.
    5    // Number of attempts to send emails.
);
```

## Personal mailing

If you have a copy of a message with many recipients, but you want to send separate emails to each recipient (no copies)

```php
<?php

/**
 * @var Ddrv\Mailer\Mailer $mailer
 * @var Ddrv\Mailer\Contract\Message $message
 */
$mailer->personal($message);
```

## Transport factory

If you use native library transport, you can use `Ddrv\Mailer\TransportFactory`.

```php
<?php

use Ddrv\Mailer\TransportFactory;

// smtp
$transport = TransportFactory::make('smtp://user:password@example.com:465/?encryption=tls&domain=example.com&sender=user%40exapmle.com&name=Informer');

// sendmail
$transport = TransportFactory::make('sendmail://localhost/?options=-i+-r+user%40example.com');

// file
$transport = TransportFactory::make('file:////path/to/mail/files');

// fake
$transport = TransportFactory::make('fake://localhost');

```
