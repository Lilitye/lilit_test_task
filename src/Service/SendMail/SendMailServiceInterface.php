<?php

namespace App\Service\SendMail;

interface SendMailServiceInterface
{
    public function sendEmail(array $requestParams, array $historicalData): void;
}