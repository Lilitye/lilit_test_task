<?php

namespace App\Service\ApiClient;

use Symfony\Component\HttpFoundation\Response;
use Exception;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiClient implements ApiClientInterface
{
    public function __construct(private HttpClientInterface $client)
    {
    }
    public function fetchApiData(string $companySymbol): array {

        if (empty($_ENV["X_RAPID_API_HOST"])) {
            throw new RuntimeException('Environment variable X_RAPID_API_HOST missing');
        }
        if (empty($_ENV["X_RAPID_API_KEY"])) {
            throw new RuntimeException('Environment variable X_RAPID_API_KEY missing');
        }
        if (empty($_ENV["X_RAPID_API_HISTORICAL_DATA"])) {
            throw new RuntimeException('Environment variable X_RAPID_API_HISTORICAL_DATA missing');
        }

        $response = $this->client->request('GET', $_ENV["X_RAPID_API_HISTORICAL_DATA"], [
            'query' => [
                'symbol' => $companySymbol
            ],
            'headers' => [
                'X-RapidAPI-Key' => $_ENV["X_RAPID_API_KEY"],
                'X-RapidAPI-Host' => $_ENV["X_RAPID_API_HOST"],
            ],
        ]);

        $responseCode = $response->getStatusCode();
        $responseData = $response->toArray();

        if ($responseCode !== Response::HTTP_OK || empty($responseData) || !isset($responseData["prices"])) {
            throw new Exception("Couldn't request historical data from rapidapi.com");
        }

        return $responseData["prices"];
    }
}