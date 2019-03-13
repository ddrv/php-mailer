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
 * Step 1. Initialization transport
 * --------------------------------
 */

/*
 * a. Sendmail
 */
$transport = new \Ddrv\Mailer\Transport\Sendmail(
    'joe@fight.club',   // sender
    '-f'                // sendmail options
);

/*
 * b. SMTP
 */
$transport = new \Ddrv\Mailer\Transport\Smtp(
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

$transport = new \Ddrv\Mailer\Transport\Fake();

/*
 * d. Other. You can implement Ddrv\Mailer\Transport\TransportInterface interface 
 */

/*
 * Step 2. Initialization Mailer
 * -----------------------------
 */
$mailer = new \Ddrv\Mailer\Mailer($transport);

/*
 * Step 3. Create message
 * ----------------------
 */

$text = <<<HTML
<h1>Welcome to Fight Club</h1>
<p>Please, read our rules in attachments</p>
HTML;
 

$message = new \Ddrv\Mailer\Message(
    'Fight Club', // subject of message
    $text,        // text of message
    true          // true for html, false for plain text
);

/*
 * Step 4. Attachments
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

$message->attachFromString(
    'fight-club.txt', // attachment name
    $rules,           // content
    'text/plain'      // content-type
);

/*
 * b. Creating attachments from file
 */

$path = '/home/tyler/docs/projects/mayhem/rules.txt';

$message->attachFromFile(
    'project-mayhem.txt',  // attachment name
     $path                 // path to attached file
);

/*
 * Step 5. Add contacts names (OPTIONAL)
 */

$mailer->addContact('tyler@fight.club', 'Tyler Durden');
$mailer->addContact('angel@fight.club', 'Angel Face');
$mailer->addContact('bob@fight.club', 'Robert Paulson');

/*
 * Step 6. Send mail
 * -----------------
 */

/*
 * a. Personal mailing (one mail per address)
 */

$mailer->send(
    $message,
    array(
        'tyler@fight.club',
        'angel@fight.club',
        'bob@fight.club',
    )
);

/*
 * b. Mass mailing (one mail to all addresses)
 */

$mailer->mass(
    $message,
    array('tyler@fight.club'), // recipients
    array('angel@fight.club'), // CC (carbon copy)
    array('bob@fight.club')    // BCC (blind carbon copy)
);
```

# Channels

You can add some channels for sending.

```php
<?php

$default = new \Ddrv\Mailer\Transport\Sendmail('user@host.name');

$noreply = new \Ddrv\Mailer\Transport\Smtp(
    'smtp.host.name',
    25,
    'no-reply@host.name',
    'password',
    'no-reply@host.name',
    'tls',
    'http://host.name'
);

$support = new \Ddrv\Mailer\Transport\Smtp(
    'smtp.host.name',
    25,
    'support@host.name',
    'password',
    'support@host.name',
    null,
    'http://host.name'
);

// channel name is \Ddrv\Mailer\Mailer::CHANNEL_DEFAULT.
// You can define your channel name in second parameter
// for example: $mailer = new \Ddrv\Mailer\Mailer($default, 'channel');
$mailer = new \Ddrv\Mailer\Mailer($default);
$mailer->setChannel($noreply, 'noreply');
$mailer->setChannel($support, 'support');

$mailer->addContact('no-reply@host.name', 'Informer');
$mailer->addContact('support@host.name', 'Support Agent');

$msg1 = new \Ddrv\Mailer\Message(
    'host.name: your account registered',
    'Your account registered! Please do not reply to this email',
    false
);

$msg2 = new \Ddrv\Mailer\Message(
    'host.name: ticket #4221 closed',
    '<p>Ticket #4221 closed</p>',
    true
);

$mailer->addContact('recipient1@host.name', 'Recipient First');
$mailer->addContact('recipient2@host.name', 'Recipient Second');
$mailer->addContact('recipient3@host.name', 'Other Recipient');

$recipients1 = array(
    'recipient1@host.name',
    'recipient2@host.name'
);
$recipients2 = array(
    'recipient2@host.name',
    'recipient3@host.name'
);

/*
 * Send to channel
 * -----------------------
 */
$mailer->send(
    $msg1,        // message
    $recipients1, // recipients
    'noreply'    // channel name
);

$mailer->send(
    $msg2,        // message
    $recipients2, // recipients
    'support'     // channel name
); 

/*
 * Send to some channels
 */
$mailer->send(
    $msg2,                      // message
    $recipients2,               // recipients
    array('support', 'noreply') // channels
); 

/*
 * Send to all channels
 */
$mailer->send($msg2, $recipients2, \Ddrv\Mailer\Mailer::CHANNEL_ALL); 

/*
 * CAUTION!
 * If the channel does not exists, the call well be skipped
 */

// If you need clear memory, you may clear contacts

$mailer->clearContacts();

```

# Logging

```php
<?php

$support = new \Ddrv\Mailer\Transport\Sendmail('support@host.name');
$noreply = new \Ddrv\Mailer\Transport\Sendmail('noreply@host.name');
$default = new \Ddrv\Mailer\Transport\Sendmail('default@host.name');
$mailer = new \Ddrv\Mailer\Mailer($support, 'support');
$mailer->setChannel($noreply, 'noreply');
$mailer->setChannel($default, 'default');

/**
 * @var Psr\Log\LoggerInterface $logger
 */

$mailer->setLogger(
    function ($log) use ($logger) {
        $logger->info($log);
    },
    array('noreply', 'support') // channels
);

```
