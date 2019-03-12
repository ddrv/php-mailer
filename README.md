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
 * Initialization Mailer class with SMTP transport
 */
$mailer = new \Ddrv\Mailer\Mailer(
    'smtp',
    array(
        'host'     => 'smtp.fight.club',  // host
        'port'     => 25,                 // port
        'username' => 'joe',              // login
        'password' => 'IAmJoesLiver',     // password
        'sender'   => 'joe@fight.club',   // sender
        'encrypt'  => null,               // encryption: 'tls', 'ssl' or null
        'domain'   => 'http://fight.club' // domain
    )
);

/*
 * Initialization Mailer class with sendmail transport
 */
$mailer = new \Ddrv\Mailer\Mailer(
    'sendmail',
    array(
        'options'     => '-f', // sendmail options
    )
);


/*
 * Create message
 */
$sender = new \Ddrv\Mailer\Address('joe@fight.club', 'Incognito');

$message = new \Ddrv\Mailer\Message(
    $sender,     // sender email
    'Fight Club',            // subject of message
    '<p>Welcome to the Fight Club</p>', // text of message
    true                  // true for html, false for plain text
);

/*
 * If need adding attachment from string, run
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
    'fight-club-rules.txt', // attachment name
    $rules, // content
    'text/plain'   // content-type
);

/*
 * If need adding attachment from file, run
 */
$message->attachFromFile(
    'project-mayhem-rules.txt',  // attachment name
    '/home/tyler/docs/projects/mayhem/rules.txt' // path to attached file
);

/*
 * Create recipients
 */
$recipients = new \Ddrv\Mailer\Book();
$recipients->add(new \Ddrv\Mailer\Address('tyler@fight.club', 'Tyler Durden'));
$recipients->add(new \Ddrv\Mailer\Address('angel@fight.club', 'Angel Face'));
$recipients->add(new \Ddrv\Mailer\Address('bob@fight.club', 'Robert Paulson'));


/*
 * Send email to addresses
 */
$mailer->send(
    $message,     // message
    $recipients,  // recipients
    false         // false for group mailing (one mail for all addresses), true for personal mailing (one mail per address)
);
```

# Channels

You can add some channels for sending.

```php
<?php

// create default channel
$mailer = new \Ddrv\Mailer\Mailer(
    'smtp',
    array(
        'host'     => 'smtp.host.name',
        'port'     => 25,
        'username' => 'no-reply@host.name',
        'password' => 'password',
        'sender'   => 'no-reply@host.name',
        'encrypt'  => 'tls',
        'domain'   => 'http://host.name'
    )
);

// create support channel
$mailer->setChannel(
    'support',
    'smtp',
    array(
        'host'     => 'smtp.host.name',
        'port'     => 25,
        'username' => 'support@host.name',
        'password' => 'password',
        'sender'   => 'support@host.name',
        'encrypt'  => null,
        'domain'   => 'http://host.name'
    )
);

$sender1 = new \Ddrv\Mailer\Address('no-reply@host.name', 'Informer');
$msg1 = new \Ddrv\Mailer\Message(
    $sender1,
    'host.name: your account registered',
    'Your account registered! Please do not reply to this email',
    false
);

$sender2 = new \Ddrv\Mailer\Address('support@host.name', 'Support Agent');
$msg2 = new \Ddrv\Mailer\Message(
    $sender2,
    'host.name: ticket #4221 closed',
    '<p>Ticket #4221 closed</p>',
    true
);

$rcpt1 = new \Ddrv\Mailer\Book();
$rcpt1->add(new \Ddrv\Mailer\Address('recipient1@host.name', 'Recipient First'));
$rcpt1->add(new \Ddrv\Mailer\Address('recipient2@host.name'));

$rcpt2 = new \Ddrv\Mailer\Book();
$rcpt2->add(new \Ddrv\Mailer\Address('recipient3@host.name', 'Other Recipient'));

$mailer->send($msg1, $rcpt1, true); // send to default channel
$mailer->send($msg2, $rcpt2, true, 'support'); // send to support channel
```

# CC and BCC

```php

<?php

$msg = new \Ddrv\Mailer\Message(
    new \Ddrv\Mailer\Address('no-reply@host.name', 'Informer'),
    'host.name: your account registered',
    'Your account registered! Please do not reply to this email',
    false
);
$msg->addCC('cc1@host.name', 'User Name');
$msg->addCC('cc2@host.name');

$msg->addBCC('bcc1@host.name', 'User Name');
$msg->addBCC('bcc2@host.name');

$rcpt = new \Ddrv\Mailer\Book();
$rcpt->add(new \Ddrv\Mailer\Address('recipient@host.name', 'Recipient'));

$mailer->send($msg, $rcpt);

```


