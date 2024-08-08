<?php

namespace App\Controller;

use App\Exception\InputDataNotValidException;
use App\Logger\LoggerService;
use App\Service\CompanyHistoricalDataService;
use App\Service\SendEmailService;
use App\Validator\InputDataValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;
use OpenApi\Attributes as OA;

readonly class CompanyDataController
{
    public function __construct(private CompanyHistoricalDataService $companyHistoricalDataService,
                                private InputDataValidator           $inputDataValidator,
                                private SendEmailService             $sendEmailService,
                                private LoggerService                $loggerService)
    {
    }

    #[Route('/api/get_historical_data', methods: ['GET'])]
    #[OA\Parameter(
        name: 'companySymbol',
        in: 'query',
        required: true,
        description: 'Company Symbol to retrieve historical data for',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'startDate',
        in: 'query',
        required: true,
        description: 'Start date for historical data',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'endDate',
        in: 'query',
        required: true,
        description: 'End date for historical data',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'email',
        in: 'query',
        required: true,
        description: 'Email to send historical data in CSV format',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns historical data for given company symbol in given range',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: "status", type: "boolean"),
                new OA\Property(property: "data", type: "array", items: new OA\Items(
                    type: 'object',
                    properties: [
                        new OA\Property(property: "date", type: "string"),
                        new OA\Property(property: "open", type: "number", format: "float"),
                        new OA\Property(property: "high", type: "number", format: "float"),
                        new OA\Property(property: "low", type: "number", format: "float"),
                        new OA\Property(property: "close", type: "number", format: "float"),
                        new OA\Property(property: "volume", type: "number", format: "int64"),
                    ]
                )),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad request',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: "status", type: "boolean"),
                new OA\Property(property: "errors", type: "array", items: new OA\Items(type: 'string')),
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: 'Internal server error',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: "status", type: "boolean"),
                new OA\Property(property: "error", type: "string"),
            ]
        )
    )]
    #[OA\Tag(name: 'Company Historical Data')]
    public function getCompanyHistoricalData(Request $request): JsonResponse
    {
        try {
            $requestParams = $request->query->all();

            $this->inputDataValidator->validate($requestParams);

            $historicalData = $this->companyHistoricalDataService->getHistoricalData($requestParams);

            $this->sendEmailService->sendEmail($requestParams, $historicalData);

            return new JsonResponse([
                'status' => true,
                'data' => $historicalData
            ]);
        } catch (InputDataNotValidException $exception) {

            return new JsonResponse([
                'status' => false,
                'errors' => $exception->getErrors()
            ], Response::HTTP_BAD_REQUEST);
        } catch (Throwable $exception) {
            $this->loggerService->log('error', $exception->getMessage());

            return new JsonResponse([
                'status' => true,
                'error' => "Internal Error",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


}