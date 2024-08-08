<?php

namespace App\Validator;

interface InputDataValidatorInterface
{
    public function validate(mixed $value): void;
}