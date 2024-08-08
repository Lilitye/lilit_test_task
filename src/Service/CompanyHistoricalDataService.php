<?php

namespace App\Service;

use App\Service\ApiClient\ApiClientInterface;
use App\Service\DataFilter\DataFilterInterface;
use DateTime;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

readonly class CompanyHistoricalDataService
{
    const HISTORICAL_DATA_KEYS = ["date", "open", "high", "low", "close", "volume"];

    public function __construct(private ApiClientInterface $apiClient, private DataFilterInterface $dataFilter)
    {
    }

    public function getHistoricalData(array $requestParams) :array
    {
        $historicalData = $this->getCompanySymbolAllHistoricalData($requestParams["companySymbol"]);
        $historicalDataFiltered = $this->dataFilter->filter($historicalData, $requestParams);

        return $this->structData($historicalDataFiltered);
    }

    protected function getCompanySymbolAllHistoricalData(string $companySymbol) :array
    {
        $cache = new FilesystemAdapter(CacheConfigService::CACHE_NAMESPACE_COMPANY_SYMBOL_HISTORICAL_DATA, 0, CacheConfigService::getRequestCachePath());

        $companyHistoricalData = $cache->get("symbol_$companySymbol", function (ItemInterface $item) use($companySymbol) {
            $cacheLifetime = $_ENV["COMPANY_SYMBOL_HISTORICAL_DATA_CACHED_LIFETIME"] ?? 86400;
            $item->expiresAfter(intval($cacheLifetime));

            return $this->apiClient->fetchApiData($companySymbol);
        });

        return $companyHistoricalData;
    }

    protected function structData(array $historicalData) :array
    {

        return array_map(function($item) {
            $retItem = [];
            foreach (self::HISTORICAL_DATA_KEYS as $key) {
                $retItem[$key] = $item[$key] ?? "";

                if($key === 'date') {
                    $retItem[$key] = (new \DateTime())->setTimestamp($item[$key])->format('Y-m-d');
                }
            }

            return $retItem;
        }, $historicalData);
    }


}