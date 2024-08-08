<?php

namespace App\Email;

interface MailServiceInterface
{
    public function sendMail(string $from, string $to, string $subject, string $body, array $attachments = []): void;
}