<?php

declare(strict_types=1);

namespace App\Yandex;

use RuntimeException;
use Throwable;

/**
 * Исключение API Яндекс Вебмастера со структурированными данными ошибки.
 */
final class WebmasterApiException extends RuntimeException
{
    /**
     * Создаёт исключение API Вебмастера.
     *
     * @param array<string, mixed> $data Декодированное тело ошибки.
     */
    public function __construct(
        private readonly int $status,
        private readonly array $data,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Возвращает HTTP-статус ответа.
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * Возвращает код ошибки API.
     */
    public function errorCode(): string
    {
        return (string) ($this->data['error_code'] ?? '');
    }

    /**
     * Возвращает текст ошибки API.
     */
    public function apiErrorMessage(): string
    {
        return (string) ($this->data['error_message'] ?? $this->data['message'] ?? '');
    }

    /**
     * Возвращает декодированное тело ошибки.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }
}
