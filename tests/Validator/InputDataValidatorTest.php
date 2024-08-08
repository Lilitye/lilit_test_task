<?php

use App\Exception\InputDataNotValidException;
use App\Service\Company\CompanyServiceInterface;
use App\Validator\InputDataValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InputDataValidatorTest extends TestCase
{
    private $validator;
    private $companyService;
    private $inputDataValidator;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->companyService = $this->createMock(CompanyServiceInterface::class);

        $this->inputDataValidator = new InputDataValidator($this->validator, $this->companyService);
    }

    public function testValidateValidData(): void
    {
        $companySymbols = ['AAPL', 'GOOG', 'MSFT'];
        $this->companyService->method('getAllCompanySymbols')
            ->willReturn($companySymbols);

        $violations = $this->createMock(ConstraintViolationList::class);
        $violations->method('count')->willReturn(0);

        $this->validator->method('validate')
            ->willReturn($violations);

        $data = [
            'companySymbol' => 'GOOG',
            'startDate' => '2024-01-01',
            'endDate' => '2024-01-02',
            'email' => 'not valid email'
        ];

        $this->inputDataValidator->validate($data);

        $this->addToAssertionCount(1);
    }

    public function testValidateInvalidData(): void
    {
        $companySymbols = ['AAPL', 'GOOG', 'MSFT'];
        $this->companyService->method('getAllCompanySymbols')
            ->willReturn($companySymbols);

        $violation = $this->createMock(ConstraintViolation::class);
        $violation->method('getMessage')
            ->willReturn("'startDate' should be a valid date");

        $violations = new ConstraintViolationList([$violation]);

        $this->validator->method('validate')
            ->willReturn($violations);

        $data = [
            'companySymbol' => 'GOOG',
            'startDate' => '2024-01-01',
            'endDate' => '2024-01-02',
            'email' => 'not valid email'
        ];

        $this->expectException(InputDataNotValidException::class);
        $this->expectExceptionMessage('Input data is not valid');

        $this->inputDataValidator->validate($data);
    }
}
