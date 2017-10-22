# Mailer
PHP Class for sending email.

# Install
## With [Composer](https://getcomposer.org/)
1. Run in console:
    ```text
    php composer.phar require ddrv/mailer
    php composer.phar install
    ```
1. Include autoload file
    ```php
    require_once('vendor/autoload.php');
    ```
## Manually install
1. Download [Archive](https://github.com/ddrv/mailer/archive/master.zip)
1. Unzip archive to /path/to/libraries/
1. Include files
    ```php
    require_once('/path/to/libraries/mailer/src/Mailer.php');
    ```

#Usage

```php
/**
 * Inititalization Mailer class. 
 * Sender must be from@host.name
 * Encoding of mail must be UTF-8
 */
$mailer = new \Ddrv\Mailer\Mailer('from@host.name','utf8');

/**
 * Set subject of mail
 */
$mailer->subject('Subject of mail');

/**
 * Add text of mail in HTML format
 */
$mailer->body('<p>Simple text</p>');

/**
 * In need adding attachment from string, run
 */
$mailer->attachFromString('content','attach1.txt');

/**
 * In need adding attachment from file, run
 */
$mailer->attachFromFile('/path/to/file','attach2.txt');

/**
 * Send email to address@host.name
 */
$mailer->send('address@host.name');
```