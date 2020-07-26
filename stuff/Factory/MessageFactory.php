<?php

namespace Stuff\Ddrv\Mailer\Factory;

use Ddrv\Mailer\Message;

final class MessageFactory
{

    private $from;

    public function __construct()
    {
        $this->from = sprintf('%s+phpunit@localhost', get_current_user());
    }

    /**
     * @param $toQuantity
     * @param int $ccQuantity
     * @param int $bccQuantity
     * @return \Ddrv\Mailer\Contract\Message
     */
    public function generateMessage($toQuantity, $ccQuantity = 0, $bccQuantity = 0)
    {
        $fish = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor ';
        $fish .= 'incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ';
        $fish .= 'ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit ';
        $fish .= 'in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat ';
        $fish .= 'cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum';
        $paragraphs = explode('. ', $fish);

        $toQuantity = (int)$toQuantity;
        if ($toQuantity < 1) {
            $toQuantity = 1;
        }
        if ($toQuantity > 20) {
            $toQuantity = 20;
        }
        $ccQuantity = (int)$ccQuantity;
        if ($ccQuantity < 0) {
            $ccQuantity = 0;
        }
        if ($ccQuantity > 20) {
            $ccQuantity = 20;
        }
        $bccQuantity = (int)$bccQuantity;
        if ($bccQuantity < 0) {
            $bccQuantity = 0;
        }
        if ($bccQuantity > 20) {
            $bccQuantity = 20;
        }
        $all = $toQuantity + $ccQuantity + $bccQuantity;
        $recipients = $this->generateRecipients($all);

        shuffle($paragraphs);
        $paragraph = $paragraphs[0];
        $words = explode(' ', $paragraph);
        $rand = rand(2, 5);
        if ($rand > count($words)) {
            $rand = count($words - 1);
        }
        $offset = rand(0, $rand - 1);
        $subject = ucfirst(strtolower(implode(' ', array_slice($words, $offset, $rand - $offset))));
        $html = '<h1>' . $subject . '</h1>';
        $text = $subject . PHP_EOL;
        foreach ($paragraphs as $paragraph) {
            $html .= '<p>' . $paragraph . '.</p>';
            $text .= PHP_EOL . $paragraph . '. ';
        }
        $text = substr($text, 0, -1);

        $message = new Message($subject, $html, $text);
        for ($n = 1; $n <= $toQuantity; $n++) {
            $recipient = array_shift($recipients);
            $message->addRecipient($recipient["email"], $recipient["name"]);
        }
        for ($n = 1; $n <= $ccQuantity; $n++) {
            $recipient = array_shift($recipients);
            $message->addRecipient($recipient["email"], $recipient["name"], Message::RECIPIENT_CC);
        }
        for ($n = 1; $n <= $bccQuantity; $n++) {
            $recipient = array_shift($recipients);
            $message->addRecipient($recipient["email"], $recipient["name"], Message::RECIPIENT_BCC);
        }
        $message->setSender($this->from, 'PHP Unit');
        return $message;
    }

    public function generateRecipients($quantity)
    {
        $quantity = (int)$quantity;
        if ($quantity < 1) {
            $quantity = 1;
        }
        if ($quantity > 100) {
            $quantity = 100;
        }
        $recipients = array();
        $tags = array('work', 'hobby', 'promo', 'spam', '', '', '');
        do {
            $name = $this->generateName();
            $surname = $this->generateName();
            $own = rand(1, 0);
            $full = rand(1, 0);
            $tag = $tags[array_rand($tags)];
            if ($own) {
                $email = $name;
            } else {
                $email = $full ? $name : substr($name, 0, 1);
                $email .= $this->generateDelimiter() . $surname;
            }
            if ($tag) {
                $email .= '+' . $tag;
            }
            if ($own) {
                $email .= '@' . $surname;
            } else {
                $email .= '@mail';
            }
            $email .= '.' . $this->generateHost();
            if (!array_key_exists($email, $recipients)) {
                $recipients[$email] = array(
                    'email' => $email,
                    'name' => ucfirst($name) . ' ' . ucfirst($surname),
                );
            }
        } while (count($recipients) < $quantity);
        return $recipients;
    }

    private function generateName()
    {

        $a = array('e', 'u', 'i', 'o', 'a', 'yo', 'yu', 'ya');
        $b = array('w', 'r', 't', 'p', 's', 'd', 'f', 'g', 'h', 'k', 'l', 'z', 'x', 'c', 'v', 'b', 'n', 'm');
        $rand = rand(2, 5);
        $result = '';
        for ($i = 1; $i <= $rand; $i++) {
            $result .= $b[array_rand($b)];
            $result .= $a[array_rand($a)];
        }
        return $result;
    }

    private function generateHost()
    {

        $a = array('com', 'org', 'net', 'site');
        return $a[array_rand($a)];
    }

    private function generateDelimiter()
    {

        $a = array('.', '', '-');
        return $a[array_rand($a)];
    }
}
