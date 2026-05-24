<?php

declare(strict_types=1);

namespace App\Http\Exception;

use RuntimeException;

/**
 * Базовое HTTP-исключение для формирования единообразных API-ошибок.
 */
class HttpException extends RuntimeException
{
    /**
     * @param array<string, mixed> $details Дополнительные данные ошибки.
     */
    public function __construct(
        string $message,
        private readonly int $statusCode,
        private readonly string $errorCode,
        private readonly array $details = []
    ) {
        parent::__construct($message);
    }

    /**
     * Возвращает HTTP-статус ошибки.
     */
    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Возвращает машинный код ошибки.
     */
    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Возвращает дополнительные данные ошибки.
     *
     * @return array<string, mixed>
     */
    public function details(): array
    {
        return $this->details;
    }
}
