<?php

namespace App\Validator;

use App\Exception\InputDataNotValidException;
use App\Service\CompanyService;
use DateTime;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class InputInputDataValidator implements InputDataValidatorInterface
{
    public function __construct(private ValidatorInterface $validator,
                                private CompanyService     $getCompanyService)
    {
    }

    /**
     * @throws InputDataNotValidException
     * @throws InvalidArgumentException
     */
    public function validate(mixed $value): void
    {
        $companySymbols = $this->getCompanyService->getAllCompanySymbols();

        $currDate = (new DateTime())->format("Y-m-d");

        $constraints = new Assert\Collection([
            'fields' => [
                'companySymbol' => [
                    new Assert\NotBlank(['message' => "'companySymbol' should not be blank"]),
                    new Assert\Choice([
                        'message' => "{{ value }} is not a valid company symbol"
                    ], $companySymbols)
                ],
                'startDate' => [
                    new Assert\NotBlank(['message' => "'startDate' should not be blank"]),
                    new Assert\Date(['message' => "'startDate' should be a valid date"]),
                    new Assert\Regex([ 'pattern'=>"/^\d{4}-\d{2}-\d{2}$/",
                        'message'=>"'startDate' must be in YYYY-mm-dd format"]),
                    new Assert\LessThanOrEqual([
                        'value' => $currDate,
                        'message' => "'startDate' should be less or equal than current date'"
                    ])
                ],
                'endDate' => [
                    new Assert\NotBlank(['message' => "'endDate' should not be blank"]),
                    new Assert\Date(['message' => "'endDate' should be a valid date"]),
                    new Assert\Regex([ 'pattern'=>"/^\d{4}-\d{2}-\d{2}$/",
                        'message'=>"'endDate' must be in YYYY-mm-dd format"]),
                    new Assert\GreaterThanOrEqual([
                        'value' => $requestParams['startDate'] ?? "",
                        'message' => "'endDate' should be less or equal than 'startDate'"
                    ]),
                    new Assert\LessThanOrEqual([
                        'value' => $currDate,
                        'message' => "'endDate' should be less or equal than current date"
                    ]),
                ],
                'email' => [
                    new Assert\NotBlank(['message' => "'email' should not be blank"]),
                    new Assert\Email(['message' => "{{ value }} is not a valid email address"])
                ]
            ],
            'missingFieldsMessage' => '{{ field }} is missing'
        ]);

        $violations = $this->validator->validate($value, $constraints);

        if ($violations->count()) {
            $errorMessages = [];
            /** @var ConstraintViolation $violation */
            foreach ($violations as $violation) {
                $errorMessages[] = $violation->getMessage();
            }

            throw new InputDataNotValidException($errorMessages, "Input data is not valid");
        }
    }

}