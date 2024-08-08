<?php

namespace App\Service\SendMail;

use App\Email\MailServiceInterface;
use App\Service\Company\CompanyServiceInterface;
use App\Service\CompanyHistoricalDataService;
use RuntimeException;

readonly class SendEmailService implements SendMailServiceInterface
{
    public function __construct(private MailServiceInterface  $mailerService,
                                private CompanyServiceInterface $companyService)
    {
    }

    public function sendEmail(array $requestParams, array $historicalData): void
    {
        if(empty($_ENV["EMAIL_FROM"])) {
            throw new RuntimeException('Environment variable EMAIL_FROM missing');
        }

        $companyName = $this->companyService->getCompanyNameBySymbol($requestParams["companySymbol"]);

        $subject = !empty($companyName) ? $companyName : $requestParams["companySymbol"];
        $body = "From {$requestParams['startDate']} to {$requestParams['endDate']}";
        $attachments = [[
            'content' => $this->getCsvContent($historicalData),
            'name' => 'historical_quotes.csv',
            'content_type' => 'text/csv'
        ]];

        $this->mailerService->sendMail($_ENV["EMAIL_FROM"], $requestParams["email"], $subject, $body, $attachments);
    }

    private function getCsvContent(array $data) :string
    {
        $headers = array_map(function ($key) {
            return ucfirst($key);
        }, CompanyHistoricalDataService::HISTORICAL_DATA_KEYS);

        $csvData = implode(",", $headers)."\n";

        foreach ($data as $row) {
            $rowArr = [];
            foreach (CompanyHistoricalDataService::HISTORICAL_DATA_KEYS as $key) {
                $rowArr[] = $row[$key];
            }
            $csvData .= implode(",", $rowArr)."\n";
        }

        return $csvData;
    }

}