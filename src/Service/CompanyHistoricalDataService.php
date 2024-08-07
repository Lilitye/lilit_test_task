<?php

namespace App\Service;

use DateTime;
use Exception;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class CompanyHistoricalDataService
{
    const HISTORICAL_DATA_KEYS = ["date", "open", "high", "low", "close", "volume"];

    public function __construct(private HttpClientInterface $client)
    {
    }

    public function getHistoricalData(array $requestParams) :array
    {
        $historicalData = $this->getCompanySymbolAllHistoricalData($requestParams["companySymbol"]);

        return $this->filterDataByRange($historicalData, $requestParams["startDate"], $requestParams["endDate"]);
    }

    private function getCompanySymbolAllHistoricalData(string $companySymbol) :array
    {
        $cache = new FilesystemAdapter(CacheConfigService::CACHE_NAMESPACE_COMPANY_SYMBOL_HISTORICAL_DATA, 0, CacheConfigService::getRequestCachePath());

        $companyHistoricalData = $cache->get("symbol_$companySymbol", function (ItemInterface $item) use($companySymbol) {
            $cacheLifetime = $_ENV["COMPANY_SYMBOL_HISTORICAL_DATA_CACHED_LIFETIME"] ?? 86400;
            $item->expiresAfter(intval($cacheLifetime));

            return $this->requestRapidApi($companySymbol);
        });

        return $companyHistoricalData;
    }

    private function requestRapidApi(string $companySymbol) :array
    {
        if (empty($_ENV["X_RAPID_API_HOST"])) {
            throw new RuntimeException('Environment variable X_RAPID_API_HOST missing');
        }
        if (empty($_ENV["X_RAPID_API_KEY"])) {
            throw new RuntimeException('Environment variable X_RAPID_API_KEY missing');
        }
        if (empty($_ENV["X_RAPID_API_HISTORICAL_DATA_URL"])) {
            throw new RuntimeException('Environment variable X_RAPID_API_HISTORICAL_DATA_URL missing');
        }

        $response = $this->client->request('GET', $_ENV["X_RAPID_API_HISTORICAL_DATA_URL"], [
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