<?php
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\CompanyHistoricalDataService;
use App\Service\CompanyService;
use App\Validator\InputDataValidator;
use App\Service\SendEmailService;
use App\Controller\CompanyDataController;
use App\Exception\InputDataNotValidException;
use App\Logger\LoggerService;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        $this->inputDataValidator = $this->createMock(InputDataValidator::class);
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
        $requestParams = [
            'companySymbol' => 'GOOG',
            'startDate' => '2024-01-01',
            'endDate' => '2024-01-02',
            'email' => 'not valid email'
        ];
        $request = new Request($requestParams);

        $historicalData = [
            [
                "date" => "2024-01-02",
                "open" => 139.60000610351562,
                "high" => 140.61500549316406,
                "low" => 137.74000549316406,
                "close" => 139.55999755859375,
                "volume" => 20071900
            ]
        ];

        $this->inputDataValidator->expects($this->once())
            ->method('validate')
            ->with($request->query->all());

        $this->companyHistoricalDataService->expects($this->once())
            ->method('getHistoricalData')
            ->with($request->query->all())
            ->willReturn($historicalData);

        $this->sendEmailService->expects($this->once())
            ->method('sendEmail')
            ->with($request->query->all(), $historicalData);

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
        $requestParams = [
            'companySymbol' => 'GOOG',
            'startDate' => '2024-01-01',
            'endDate' => '2024-01-02',
            'email' => 'not valid email'
        ];

        $request = new Request($requestParams);

        $errors = [
            "\"not valid email\" is not a valid email address"
        ];

        $this->inputDataValidator->expects($this->once())
            ->method('validate')
            ->with($request->query->all())
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
        $requestParams = [
            'companySymbol' => 'GOOG',
            'startDate' => '2021-01-01',
            'endDate' => '2021-01-31',
            'email' => 'test@test.com'
        ];

        $request = new Request($requestParams);

        $this->inputDataValidator->expects($this->once())
            ->method('validate')
            ->with($request->query->all());

        $this->companyHistoricalDataService->expects($this->once())
            ->method('getHistoricalData')
            ->with($request->query->all())
            ->willThrowException(new \RuntimeException('Some error'));

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
