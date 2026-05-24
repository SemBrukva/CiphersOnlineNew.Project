<?php

declare(strict_types=1);

namespace App\Validation;

use RuntimeException;

/**
 * Исключение, выбрасываемое при провале валидации.
 *
 * Содержит карту ошибок: поле → список сообщений об ошибках.
 */
final class ValidationException extends RuntimeException
{
    /**
     * Создаёт исключение с набором ошибок валидации.
     *
     * @param array<string, string[]> $errors Ошибки по полям.
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('The given data was invalid.');
    }

    /**
     * Возвращает карту ошибок валидации: поле → список сообщений.
     *
     * @return array<string, string[]>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Возвращает HTTP-статус ошибки валидации.
     */
    public function httpStatusCode(): int
    {
        return 422;
    }
}
