<?php
namespace App\Tests\Service;

use App\Email\MailServiceInterface;
use App\Service\Company\CompanyServiceInterface;
use App\Service\SendMail\SendEmailService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SendEmailServiceTest extends TestCase
{
    private $mailerService;
    private $companyService;
    private $sendEmailService;

    protected function setUp(): void
    {
        $this->mailerService = $this->createMock(MailServiceInterface::class);
        $this->companyService = $this->createMock(CompanyServiceInterface::class);

        $this->sendEmailService = new SendEmailService($this->mailerService, $this->companyService);
    }

    public function testSendEmailWithValidData(): void
    {
        $requestParams = [
            'companySymbol' => 'GOOG',
            'startDate' => '2023-01-01',
            'endDate' => '2023-01-10',
            'email' => 'test@example.com'
        ];

        $historicalData = [
            [
                "date" => "2024-01-02",
                "open" => 139.60000610351562,
                "high" => 140.61500549316406,
                "low" => 137.74000549316406,
                "close" => 139.55999755859375,
                "volume" => 20071900
            ]
        ];

        $companyName = 'Google Inc.';

        $_ENV["EMAIL_FROM"] = 'sender@example.com';

        $this->companyService->method('getCompanyNameBySymbol')
            ->with($requestParams["companySymbol"])
            ->willReturn($companyName);

        $this->mailerService->expects($this->once())
            ->method('sendMail')
            ->with(
                $_ENV["EMAIL_FROM"],
                $requestParams['email'],
                $companyName,
                "From {$requestParams['startDate']} to {$requestParams['endDate']}",
                $this->callback(function($attachments) {
                    return isset($attachments[0]['content'], $attachments[0]['name'], $attachments[0]['content_type']) &&
                        $attachments[0]['name'] === 'historical_quotes.csv' &&
                        $attachments[0]['content_type'] === 'text/csv';
                })
            );

        $this->sendEmailService->sendEmail($requestParams, $historicalData);
    }

    public function testSendEmailThrowsExceptionWhenEmailFromIsMissing(): void
    {
        $requestParams = [
            'companySymbol' => 'GOOG',
            'startDate' => '2023-01-01',
            'endDate' => '2023-01-10',
            'email' => 'test@example.com'
        ];

        $historicalData = [
            [
                "date" => "2024-01-02",
                "open" => 139.60000610351562,
                "high" => 140.61500549316406,
                "low" => 137.74000549316406,
                "close" => 139.55999755859375,
                "volume" => 20071900
            ]
        ];

        unset($_ENV["EMAIL_FROM"]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Environment variable EMAIL_FROM missing');

        $this->sendEmailService->sendEmail($requestParams, $historicalData);
    }
}