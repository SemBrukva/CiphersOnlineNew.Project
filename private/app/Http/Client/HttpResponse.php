<?php

declare(strict_types=1);

namespace App\Http\Client;

/**
 * Представляет HTTP-ответ от удалённого сервера.
 */
final class HttpResponse
{
    /**
     * @param int                   $status  HTTP-статус ответа.
     * @param array<string, string> $headers Заголовки ответа.
     * @param string                $body    Тело ответа.
     */
    public function __construct(
        private readonly int $status,
        private readonly array $headers,
        private readonly string $body
    ) {
    }

    /**
     * Возвращает HTTP-статус ответа.
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * Возвращает true, если статус находится в диапазоне 2xx.
     */
    public function ok(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /**
     * Псевдоним ok().
     */
    public function successful(): bool
    {
        return $this->ok();
    }

    /**
     * Возвращает true, если статус находится в диапазоне 4xx.
     */
    public function clientError(): bool
    {
        return $this->status >= 400 && $this->status < 500;
    }

    /**
     * Возвращает true, если статус находится в диапазоне 5xx.
     */
    public function serverError(): bool
    {
        return $this->status >= 500 && $this->status < 600;
    }

    /**
     * Возвращает true, если статус является ошибкой (4xx или 5xx).
     */
    public function failed(): bool
    {
        return $this->clientError() || $this->serverError();
    }

    /**
     * Возвращает тело ответа как строку.
     */
    public function body(): string
    {
        return $this->body;
    }

    /**
     * Декодирует тело ответа как JSON.
     *
     * Если передан $key — возвращает соответствующее значение или $default.
     * Без аргументов возвращает весь декодированный массив или $default.
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        $data = json_decode($this->body, true);

        if (!is_array($data)) {
            return $default;
        }

        if ($key === null) {
            return $data;
        }

        return $data[$key] ?? $default;
    }

    /**
     * Возвращает значение заголовка ответа (регистронезависимо) или null.
     */
    public function header(string $name): ?string
    {
        $lower = strtolower($name);

        foreach ($this->headers as $k => $v) {
            if (strtolower($k) === $lower) {
                return $v;
            }
        }

        return null;
    }

    /**
     * Возвращает все заголовки ответа.
     *
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Бросает HttpException, если ответ содержит статус 4xx или 5xx.
     *
     * @throws HttpException
     */
    public function throw(): static
    {
        if ($this->failed()) {
            throw new HttpException($this);
        }

        return $this;
    }
}
