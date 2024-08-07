<?php

namespace App\Email;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

readonly class MailerService
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendMail(string $from, string $to, string $subject, string $body, array $attachments = []): void
    {
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