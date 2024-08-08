<?php

namespace App\Service\Company;

use App\Service\CacheConfigService;
use Exception;
use RuntimeException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class CompanyService implements CompanyServiceInterface
{
    private static array $allCompanies = [];

    public function getAllCompanies() :array
    {
        if(empty(self::$allCompanies)) {
            $cache = new FilesystemAdapter(CacheConfigService::CACHE_NAMESPACE_COMPANIES, 0, CacheConfigService::getRequestCachePath());

            self::$allCompanies = $cache->get("all_companies", function (ItemInterface $item) {
                $cacheLifetime = $_ENV["COMPANIES_CACHED_LIFETIME"] ?? 3600;
                $item->expiresAfter(intval($cacheLifetime));

                $companiesJsonURL = $_ENV['COMPANIES_JSON_URL'] ?? '';

                if (empty($companiesJsonURL)) {
                    throw new RuntimeException('Environment variable COMPANIES_JSON_URL missing');
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

    public function getCompanyNameBySymbol(string $companySymbol) :string
    {
        $company = $this->getCompanyBySymbol($companySymbol);

        return $company["Company Name"] ?? "";
    }

}