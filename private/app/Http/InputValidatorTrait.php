<?php

declare(strict_types=1);

namespace App\Http;

use App\Validation\ValidationException;
use App\Validation\Validator;

/**
 * Трейт с общей логикой валидации входных данных.
 */
trait InputValidatorTrait
{
    /**
     * Валидирует данные по правилам и выбрасывает ValidationException при ошибке.
     *
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string>> $rules
     * @throws ValidationException
     */
    protected static function validateInput(array $data, array $rules): void
    {
        $validator = new Validator($data, $rules);
        $validator->validate();
    }
}
