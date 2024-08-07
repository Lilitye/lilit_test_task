<?php

namespace App\Email;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerService
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function sendMail(string $from, string $to, string $subject, string $body, array $attachments = []) {
        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->html($body);

        foreach ($attachments as $attachment) {
            if(!empty($attachment["path"])) {
                $email->attachFromPath($attachment["path"], $attachment['name'], $attachment['content_type']);
            } elseif(!empty($attachment["content"])) {
                $email->attach($attachment["content"], $attachment['name'], $attachment['content_type']);
            }
        }

        $this->mailer->send($email);
    }
}