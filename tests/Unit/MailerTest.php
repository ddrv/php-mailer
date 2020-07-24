<?php

namespace Tests\Ddrv\Mailer\Unit;

use Ddrv\Mailer\Mailer;
use Ddrv\Mailer\Message;
use Ddrv\Mailer\Spool\MemorySpool;
use PHPUnit\Framework\TestCase;
use Tests\Ddrv\Mailer\Support\Factory\MessageFactory;
use Tests\Ddrv\Mailer\Support\Mock\Transport\MockTransport;

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
        $this->transport = new MockTransport();
        $this->mailer = new Mailer(new MemorySpool($this->transport));
    }

    public function testSimpleMassSend()
    {
        $message = $this->factory->generateMessage(5);
        $this->mailer->send($message);
        $this->assertMessage($message, $this->transport->pull());
    }

    public function testSimplePersonalSend()
    {
        $message = $this->factory->generateMessage(5);
        $this->mailer->personal($message);
        $messages = $message->getPersonalMessages();
        foreach ($messages as $message) {
            $this->assertMessage($message, $this->transport->pull());
        }
        $this->assertNull($this->transport->pull());
    }

    public function testSpoolMassSend()
    {
        $messages = $this->generateMessages(5);
        foreach ($messages as $message) {
            $this->mailer->send($message, 1);
        }
        $this->assertNull($this->transport->pull());
        $this->mailer->flush(1);
        $this->assertMessage(array_shift($messages), $this->transport->pull());
        $this->assertNull($this->transport->pull());
        $this->mailer->flush();
        foreach ($messages as $message) {
            $this->assertMessage($message, $this->transport->pull());
        }
        $this->assertNull($this->transport->pull());
    }

    public function testSpoolPersonalSend()
    {
        $message = $this->factory->generateMessage(5);
        $messages = $message->getPersonalMessages();
        $this->mailer->personal($message, 1);
        $this->assertNull($this->transport->pull());
        $this->mailer->flush(1);
        $this->assertMessage(array_shift($messages), $this->transport->pull());
        $this->assertNull($this->transport->pull());
        $this->mailer->flush();
        foreach ($messages as $expected) {
            $this->assertMessage($expected, $this->transport->pull());
        }
        $this->assertNull($this->transport->pull());
    }

    private function generateMessages($quantity)
    {
        $messages = array();
        for ($i = 1; $i <= $quantity; $i++) {
            $messages[] = $this->factory->generateMessage(rand(1, 20), rand(1, 20), rand(1, 20));
        }
        return $messages;
    }

    public static function assertMessage(Message $expected, Message $actual)
    {
        self::assertSame($expected->getSubject(), $actual->getSubject());
        self::assertSame($expected->getBody(), $actual->getBody());
        self::assertSame($expected->getHeaders(), $actual->getHeaders());
        self::assertSame($expected->getRecipients(), $actual->getRecipients());
    }
}
