<?php

namespace Tests\Ddrv\Mailer\Support\Factory;

use Ddrv\Mailer\Message;
use Faker\Factory;
use Faker\Generator;

class MessageFactory
{

    /**
     * @var Generator
     */
    private $faker;

    private $from;

    public function __construct()
    {
        $this->faker = Factory::create();
        $this->from = sprintf("PHPUnit Test <%s+phpunit@localhost>", get_current_user());
    }

    public function generateMessage($toQuantity, $ccQuantity = 0, $bccQuantity = 0)
    {
        $toQuantity = (int)$toQuantity;
        if ($toQuantity < 1) $toQuantity = 1;
        if ($toQuantity > 20) $toQuantity = 20;
        $ccQuantity = (int)$ccQuantity;
        if ($ccQuantity < 0) $ccQuantity = 0;
        if ($ccQuantity > 20) $ccQuantity = 20;
        $bccQuantity = (int)$bccQuantity;
        if ($bccQuantity < 0) $bccQuantity = 0;
        if ($bccQuantity > 20) $bccQuantity = 20;
        $all = $toQuantity + $ccQuantity + $bccQuantity;
        $recipients = $this->generateRecipients($all);

        $subject = $this->faker->text(50);
        $text = $this->faker->randomHtml();
        $message = new Message($subject, $text, true);
        for ($n=1; $n<=$toQuantity; $n++) {
            $recipient = array_shift($recipients);
            $message->addTo($recipient["email"], $recipient["name"]);
        }
        for ($n=1; $n<=$ccQuantity; $n++) {
            $recipient = array_shift($recipients);
            $message->addCc($recipient["email"], $recipient["name"]);
        }
        for ($n=1; $n<=$bccQuantity; $n++) {
            $recipient = array_shift($recipients);
            $message->addBcc($recipient["email"], $recipient["name"]);
        }
        $message->setHeader("From", $this->from);
        return $message;
    }

    public function generateRecipients($quantity)
    {
        $quantity = (int)$quantity;
        if ($quantity < 1) $quantity = 1;
        if ($quantity > 100) $quantity = 100;
        $recipients = array();
        do {
            $email = $this->faker->email;
            if (!array_key_exists($email, $recipients)) {
                $recipients[$email] = array(
                    "email" => $email,
                    "name" => $this->faker->name,
                );
            }
        } while (count($recipients) < $quantity);
        return $recipients;
    }
}