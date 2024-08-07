<?php

namespace App\Controller;

use App\Exception\InputDataNotValidException;
use App\Logger\LoggerService;
use App\Service\GetCompanyDataService;
use App\Service\SendEmailService;
use App\Validator\InputDataValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class GetCompanyDataController
{
    public function __construct(private GetCompanyDataService $getCompanyDataService,
                                private InputDataValidatorInterface $inputDataValidator,
                                private SendEmailService $sendEmailService,
                                private LoggerService $loggerService)
    {
    }

    #[Route('/api/get_historical_data', methods: ['POST'])]
    public function getCompanyHistoricalData(Request $request): JsonResponse
    {
        try {
            $requestParams = $request->request->all();

            $this->inputDataValidator->validate($requestParams);

            $historicalData = $this->getCompanyDataService->getCompanyHistoricalData($requestParams);

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
                'error' => "Internal Error"
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


}