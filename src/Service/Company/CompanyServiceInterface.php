<?php

namespace App\Service\Company;

interface CompanyServiceInterface
{
    public function getAllCompanies() :array;

    public function getAllCompanySymbols() :array;

    public function getCompanyBySymbol(string $companySymbol) :array;

    public function getCompanyNameBySymbol(string $companySymbol) :string;
}