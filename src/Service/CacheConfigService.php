<?php

namespace App\Service;

class CacheConfigService
{
    const CACHE_NAMESPACE_COMPANIES = 'Companies';
    const CACHE_NAMESPACE_COMPANY_SYMBOL_HISTORICAL_DATA = 'Company_Symbol_Historical_Data';

    protected static string $requestCachePath = "";

    public static function getRequestCachePath(): string {
        if (!empty(self::$requestCachePath)) {
            return self::$requestCachePath;
        }

        $filePathParts = explode("src", __DIR__);
        $projectDir = rtrim($filePathParts[0], "/");

        return $projectDir . "/var/cache/request-cache";
    }
}