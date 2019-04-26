[![Latest Stable Version](https://img.shields.io/packagist/v/ddrv/mailer.svg?style=flat-square)](https://packagist.org/packages/ddrv/mailer)
[![Total Downloads](https://img.shields.io/packagist/dt/ddrv/mailer.svg?style=flat-square)](https://packagist.org/packages/ddrv/mailer/stats)
[![License](https://img.shields.io/packagist/l/ddrv/mailer.svg?style=flat-square)](https://github.com/ddrv/mailer/blob/master/LICENSE)
[![PHP](https://img.shields.io/packagist/php-v/ddrv/mailer.svg?style=flat-square)](https://php.net)


# Mailer
PHP library for sending email.

# Install
## With [Composer](https://getcomposer.org/)
1. Run in console:
    ```text
    php composer.phar require ddrv/mailer:~4.0
    ```
1. Include autoload file
    ```php
    require_once('vendor/autoload.php');
    ```


# Usage

```php
<?php

use \Ddrv\Mailer\Transport\SendmailTransport;
use \Ddrv\Mailer\Transport\SmtpTransport;
use \Ddrv\Mailer\Transport\FakeTransport;
use \Ddrv\Mailer\Spool\MemorySpool;
use \Ddrv\Mailer\Spool\FileSpool;
use \Ddrv\Mailer\Mailer;
use \Ddrv\Mailer\Message;

/*
 * Step 1. Initialization transport
 * --------------------------------
 */

/*
 * a. Sendmail
 */
$transport = new SendmailTransport(
    '-f'                // sendmail options
);

/*
 * b. SMTP
 */
$transport = new SmtpTransport(
    'smtp.fight.club',  // host
    25,                 // port
    'joe',              // login
    'IAmJoesLiver',     // password
    'joe@fight.club',   // sender
    null,               // encryption: 'tls', 'ssl' or null
    'http://fight.club' // domain
);

/*
 * c. Fake (emulation send emails)
 */

$transport = new FakeTransport();

/*
 * d. Other. You can implement Ddrv\Mailer\Transport\TransportInterface interface 
 */

/*
 * Step 2. Initialization spool
 * -----------------------------
 */

/*
 * a. File spool. 
 */
$spool = new FileSpool($transport, sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mail');

/*
 * b. Memory spool. 
 */
$spool = new MemorySpool($transport);

/*
 * c. Other. You can implement Ddrv\Mailer\SpoolInterface interface 
 */

/*
 * Step 3. Initialization Mailer
 * -----------------------------
 */
$mailer = new Mailer($spool);

// If you need a replace header "From" in all messages, set your sender in second parameter 
$mailer = new Mailer($spool, "Fight Club Informer <info@fight.club>");

/*
 * Step 4. Create message
 * ----------------------
 */
 

$message1 = new Message(
    'Test message',           // subject of message
    '<h1>Hello, world!</h1>', // html body
    'Hello, world!'           // plain body
);
$message1->addTo('recipient@example.com', 'My Friend');

$html = <<<HTML
<h1>Welcome to Fight Club</h1>
<p>Please, read our rules in attachments</p>
HTML;

$text = <<<TEXT
Welcome to Fight Club
Please, read our rules in attachments
TEXT;

$message2 = new Message();
$message2
    ->setSubject('Fight Club')
    ->setHtmlBody($html)
    ->setPlainBody($text)
;

/*
 * Step 5. Attachments
 * -------------------
 */

/*
 * a. Creating attachment from string
 */
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

$message2->attachFromString(
    'fight-club.txt', // attachment name
    $rules,           // content
    'text/plain'      // content-type
);

/*
 * b. Creating attachments from file
 */
$path = '/home/tyler/docs/projects/mayhem/rules.txt';

$message2->attachFromFile(
    'project-mayhem.txt',  // attachment name
     $path                 // path to attached file
);

/*
 * Step 6. Add recipients
 */
$message2->addTo('tyler@fight.club', 'Tyler Durden');
$message2->addCc('bob@fight.club', 'Robert Paulson');
$message2->addBcc('angel@fight.club', 'Angel Face');

/*
 * Step 7. Send mail
 * -----------------
 */

/*
 * a. Simple send to all recipients
 */
$mailer->send($message1);

/*
 * b. Spooling
 * For add message to spool, you need set second parameter as positive integer
 */
$mailer->send($message1, 2);
$mailer->send($message2, 1);

// Send from spool.
$mailer->flush();

/*
 * You can set limit for send messages from spool
 */
$mailer->flush(5);

/*
 * Step 8. Personal mailing
 * You can send one message with many recipient as many mails per recipient 
 * (messages will be without CC fnd BCC headers and include only one email on To header).
 */
$mailer->personal($message2); // without spool
// or
$mailer->personal($message2, 1); // with spool
$mailer->flush();

