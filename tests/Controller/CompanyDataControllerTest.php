<?php
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;
use App\Service\CompanyHistoricalDataService;
use App\Validator\InputDataValidatorInterface;
use App\Service\SendEmailService;
use App\Controller\CompanyDataController;
use App\Exception\InputDataNotValidException;
use App\Logger\LoggerService;

class CompanyDataControllerTest extends TestCase
{
    private $companyHistoricalDataService;
    private $inputDataValidator;
    private $sendEmailService;
    private $loggerService;
    private $controller;

    protected function setUp(): void
    {
        $this->companyHistoricalDataService = $this->createMock(CompanyHistoricalDataService::class);
        $this->inputDataValidator = $this->createMock(InputDataValidatorInterface::class);
        $this->sendEmailService = $this->createMock(SendEmailService::class);
        $this->loggerService = $this->createMock(LoggerService::class);

        $this->controller = new CompanyDataController(
            $this->companyHistoricalDataService,
            $this->inputDataValidator,
            $this->sendEmailService,
            $this->loggerService
        );
    }

    public function testGetCompanyHistoricalDataSuccess()
    {
        $requestParams = ['company_id' => 1, 'start_date' => '2021-01-01', 'end_date' => '2021-01-31'];
        $request = new Request([], $requestParams);
        $historicalData = ['some' => 'data'];

        $this->inputDataValidator->expects($this->once())
            ->method('validate')
            ->with($requestParams);

        $this->companyHistoricalDataService->expects($this->once())
            ->method('getHistoricalData')
            ->with($requestParams)
            ->willReturn($historicalData);

        $this->sendEmailService->expects($this->once())
            ->method('sendEmail')
            ->with($requestParams, $historicalData);

        $response = $this->controller->getCompanyHistoricalData($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_OK, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => true,
            'data' => $historicalData
        ]), $response->getContent());
    }

    public function testGetCompanyHistoricalDataValidationError()
    {
        $requestParams = ['company_id' => 1, 'start_date' => 'invalid-date', 'end_date' => '2021-01-31'];
        $request = new Request([], $requestParams);
        $errors = ['start_date' => 'Invalid date format'];

        $this->inputDataValidator->expects($this->once())
            ->method('validate')
            ->with($requestParams)
            ->willThrowException(new InputDataNotValidException($errors));

        $response = $this->controller->getCompanyHistoricalData($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => false,
            'errors' => $errors
        ]), $response->getContent());
    }

    public function testGetCompanyHistoricalDataInternalError()
    {
        $requestParams = ['company_id' => 1, 'start_date' => '2021-01-01', 'end_date' => '2021-01-31'];
        $request = new Request([], $requestParams);

        $this->inputDataValidator->expects($this->once())
            ->method('validate')
            ->with($requestParams);

        $this->companyHistoricalDataService->expects($this->once())
            ->method('getHistoricalData')
            ->with($requestParams)
            ->willThrowException(new \Exception('Some error'));

        $this->loggerService->expects($this->once())
            ->method('log')
            ->with('error', 'Some error');

        $response = $this->controller->getCompanyHistoricalData($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => true,
            'error' => "Internal Error"
        ]), $response->getContent());
    }
}
