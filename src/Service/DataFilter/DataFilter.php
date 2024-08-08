<?php

namespace App\Service\DataFilter;

use DateTime;

class DataFilter implements DataFilterInterface
{

    public function filter(array $data, array $filterParams = []): array
    {
        $startDateTimestamp = DateTime::createFromFormat('Y-m-d', $filterParams["startDate"])->getTimestamp();
        $endDateTimestamp = DateTime::createFromFormat('Y-m-d', $filterParams["endDate"])->getTimestamp();

        $dataInRange = array_filter($data, function($item) use ($startDateTimestamp, $endDateTimestamp) {
            return $item["date"] >= $startDateTimestamp && $item["date"] <= $endDateTimestamp;
        });

        return array_values($dataInRange);
    }
}