# Mailer
PHP Class for sending email.

## For example

```php

$params = [
    'sender' => 'sender@host.name',
    'charset' => 'utf8',
];

$mailer = new \Ddrv\Mailer\Mailer($params);

// Simple text
$mailer->send('user@host.name','Test Simple Text', 'This is simple text');

// Attachments
$attachments = array(
    // Attachment from string
    array(
        'name' => 'attach_from_string.txt',
        'type' => 'string',
        'content' => 'Content of attach 1',
    ),
    // Attachment from file
    array(
        'name' => 'attach_from_file.txt',
        'type' => 'file',
        'content' => '/path/to/file',
    )
);
$mailer->send('user@host.name','Test Attachments', '<i>This is Body</i>', $attachments);
```