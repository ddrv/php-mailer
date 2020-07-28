<?php

namespace Tests\Ddrv\Mailer;

use Ddrv\Mailer\Contract\Message as MessageContract;
use Ddrv\Mailer\Exception\RecipientsListEmptyException;
use Ddrv\Mailer\Mailer;
use PHPUnit\Framework\TestCase;
use Stuff\Ddrv\Mailer\Factory\MessageFactory;
use Stuff\Ddrv\Mailer\Transport\MockTransport;

class MailerTest extends TestCase
{

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var MockTransport
     */
    private $transport;

    /**
     * @var MessageFactory
     */
    private $factory;

    public function setUp()
    {
        parent::setUp();
        $this->factory = new MessageFactory();
        $this->transport = new MockTransport(function ($log) {
        });
        $this->mailer = new Mailer($this->transport);
    }

    /**
     * @throws RecipientsListEmptyException
     */
    public function testSimpleMassSend()
    {
        $message = $this->factory->generateMessage(5);
        $this->mailer->send($message);
        $last = $this->transport->pull();
        $this->assertMessage($message, $last);
    }

    /**
     * @throws RecipientsListEmptyException
     */
    public function testSimplePersonalSend()
    {
        $message = $this->factory->generateMessage(5);
        $this->mailer->personal($message);
        $this->assertSame(5, $this->transport->count());
        $ids = array();
        foreach ($message->getRecipients() as $recipient) {
            $msg = $this->transport->pull();
            $recipientName = $message->getRecipientName($recipient);
            if ((strpos($recipientName, ' ') !== false || strpos($recipientName, "\t") !== false) && $recipientName) {
                $recipientName = '"' . $recipientName . '"';
            }
            $fullRecipient = $recipientName;
            if ($recipientName) {
                $fullRecipient .= ' ';
            }
            $fullRecipient .= '<' . $recipient . '>';
            $headers = $msg->getHeadersRaw();
            $headers = preg_replace('/=\r\n\s+/ui', '', $headers);
            $arr = explode("\r\n", $headers);
            $id = null;
            $to = null;
            foreach ($arr as $item) {
                $item = mb_decode_mimeheader($item);
                list($header, $value) = array_replace(array('', ''), explode(':', $item));
                $header = strtolower(trim($header));
                $value = trim($value);
                if ($header === 'message-id') {
                    $id = $value;
                }
                if ($header === 'to') {
                    $to = $value;
                }
            }
            $this->assertArrayNotHasKey($id, $ids);
            $this->assertSame($to, $fullRecipient);
            $this->assertSame($message->getBodyRaw(), $msg->getBodyRaw());
            $ids[$id] = true;
        }
        $this->assertNull($this->transport->pull());
    }

    public static function assertMessage(MessageContract $expected, MessageContract $actual)
    {
        self::assertSame($expected->getHeader('subject'), $actual->getHeader('subject'));
        self::assertSame($expected->getBodyRaw(), $actual->getBodyRaw());
        self::assertSame($expected->getHeadersRaw(), $actual->getHeadersRaw());
        self::assertSame(implode(', ', $expected->getRecipients()), implode(', ', $actual->getRecipients()));
    }
}
