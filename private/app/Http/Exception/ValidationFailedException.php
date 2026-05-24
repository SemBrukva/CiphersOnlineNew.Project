<?php

declare(strict_types=1);

namespace App\Http\Exception;

/**
 * Исключение для доменных ошибок валидации API.
 */
final class ValidationFailedException extends HttpException
{
    /**
     * @param array<string, mixed> $details Дополнительные поля ошибки.
     */
    public function __construct(
        string $message = 'The given data was invalid.',
        array $details = []
    ) {
        parent::__construct($message, 422, 'validation_failed', $details);
    }
}
