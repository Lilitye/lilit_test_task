<?php

namespace App\Service\ApiClient;

interface ApiClientInterface
{
    public function fetchApiData(string $companySymbol): array;
}