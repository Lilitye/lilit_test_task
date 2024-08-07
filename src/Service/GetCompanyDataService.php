<?php

namespace App\Service;

use DateTime;
use Exception;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GetCompanyDataService
{
    use CacheConfigTrait;

    const HISTORICAL_DATA_KEYS = ["date", "open", "high", "low", "close", "volume"];

    public function __construct(private GetCompanyService $getCompanyService,
                                private HttpClientInterface $client)
    {
    }

    public function getCompanyHistoricalData(array $requestParams) :array
    {
        $historicalData = $this->getCompanySymbolAllHistoricalData($requestParams["companySymbol"]);
        $historicalDataInRange = $this->filterDataByRange($historicalData, $requestParams["startDate"], $requestParams["endDate"]);

        return $historicalDataInRange;
    }

    private function getCompanySymbolAllHistoricalData(string $companySymbol) :array
    {
        $cache = new FilesystemAdapter(GetCompanyService::CACHE_NAMESPACE_COMPANY_SYMBOL_HISTORICAL_DATA, 0, self::getRequestCachePath());

        $companyHistoricalData = $cache->get("symbol_$companySymbol", function (ItemInterface $item) use($companySymbol) {
            $cacheLifetime = $_ENV["COMPANY_SYMBOL_HISTORICAL_DATA_CACHED_LIFETIME"] ?? 86400;
            $item->expiresAfter(intval($cacheLifetime));

            return $this->requestRapidapi($companySymbol);
        });

        return $companyHistoricalData;
    }

    private function requestRapidapi(string $companySymbol) :array
    {
        $host = $_ENV["X_RAPIDAPI_HOST"];
        $apiKey = $_ENV["X_RAPIDAPI_KEY"];

        $apiURL = "https://{$host}/{$_ENV["X_RAPIDAPI_HISTORICAL_DATA"]}";

        $response = $this->client->request('GET', $apiURL, [
            'query' => [
                'symbol' => $companySymbol
            ],
            'headers' => [
                'X-RapidAPI-Key' => $apiKey,
                'X-RapidAPI-Host' => $host,
            ],
        ]);

        $responseCode = $response->getStatusCode();
        $responseData = $response->toArray();

        if ($responseCode !== Response::HTTP_OK || empty($responseData) || !isset($responseData["prices"])) {
            throw new Exception("Couldn't request historical data from rapidapi.com");
        }

        return $responseData["prices"];
    }

    private function filterDataByRange(array $historicalData, string $startDate, string $endDate)
    {
        $startDateTimestamp = DateTime::createFromFormat('Y-m-d', $startDate)->getTimestamp();
        $endDateTimestamp = DateTime::createFromFormat('Y-m-d', $endDate)->getTimestamp();

        $historicalDataInRange = array_filter($historicalData, function($item) use ($startDateTimestamp, $endDateTimestamp) {

            return $item["date"] >= $startDateTimestamp && $item["date"] <= $endDateTimestamp;
        });

        return array_map(function($item) {
            $retItem = [];
            foreach (self::HISTORICAL_DATA_KEYS as $key) {
                $retItem[$key] = $item[$key] ?? "";

                if($key === 'date') {
                    $retItem[$key] = (new \DateTime())->setTimestamp($item[$key])->format('Y-m-d');
                }
            }

            return $retItem;
        }, array_values($historicalDataInRange));
    }


}