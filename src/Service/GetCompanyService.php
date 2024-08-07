<?php

namespace App\Service;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\Exception\EnvNotFoundException;
use Exception;
use Symfony\Contracts\Cache\ItemInterface;

class GetCompanyService
{
    use CacheConfigTrait;

    private static array $allCompanies = [];

    public function getAllCompanies() :array
    {
        if(empty(self::$allCompanies)) {
            $cache = new FilesystemAdapter(GetCompanyService::CACHE_NAMESPACE_COMPANIES, 0, self::getRequestCachePath());

            self::$allCompanies = $cache->get("all_companies", function (ItemInterface $item) {
                $cacheLifetime = $_ENV["COMPANIES_CACHED_LIFETIME"] ?? 3600;
                $item->expiresAfter(intval($cacheLifetime));

                $companiesJsonURL = $_ENV['COMPANIES_JSON_URL'] ?? '';

                if (empty($companiesJsonURL)) {
                    throw new EnvNotFoundException('Environment variable COMPANIES_JSON_URL missing');
                }

                if (($companiesJson = file_get_contents($companiesJsonURL)) === false) {
                    throw new Exception("Failed to get content from URL: $companiesJsonURL");
                }

                return !empty($companiesJson) ? json_decode($companiesJson, true) : [];
            });
        }

        return self::$allCompanies;
    }

    public function getAllCompanySymbols() :array
    {
        $companies = $this->getAllCompanies();

        return array_column($companies, 'Symbol');
    }

    public function getCompanyBySymbol(string $companySymbol) :array
    {
        $companies = $this->getAllCompanies();

        $company = [];
        foreach ($companies as $companyItem) {
            if($companyItem['Symbol'] === $companySymbol) {
                $company = $companyItem;
                break;
            }
        }

        return $company;
    }

}