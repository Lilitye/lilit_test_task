<?php

namespace App\Service\DataFilter;

interface DataFilterInterface
{
    public function filter(array $data, array $filterParams = []): array;

}