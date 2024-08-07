<?php

namespace App\Service;

use App\Email\MailerService;
use RuntimeException;

readonly class SendEmailService
{
    public function __construct(private MailerService  $mailerService,
                                private CompanyService $companyService)
    {
    }

    public function sendEmail(array $requestParams, array $historicalData): void
    {
        if(empty($_ENV["EMAIL_FROM"])) {
            throw new RuntimeException('Environment variable EMAIL_FROM missing');
        }

        $companyData = $this->companyService->getCompanyBySymbol($requestParams["companySymbol"]);
        $subject = $companyData["Company Name"] ?? '';
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