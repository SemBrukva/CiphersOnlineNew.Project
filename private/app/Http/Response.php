<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Представляет HTTP-ответ: статус-код, заголовки и тело.
 */
final readonly class Response
{
    /**
     * Создаёт HTTP-ответ.
     *
     * @param string               $content    Тело ответа.
     * @param int                  $statusCode HTTP-статус.
     * @param array<string,string> $headers    Дополнительные заголовки.
     */
    public function __construct(
        private string $content = '',
        private int    $statusCode = 200,
        private array  $headers = []
    ) {
    }

    /**
     * Создаёт JSON-ответ с корректным заголовком Content-Type.
     *
     * @param mixed $data   Данные для сериализации в JSON.
     * @param int   $status HTTP-статус ответа.
     */
    public static function json(mixed $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            $status,
            ['Content-Type' => 'application/json; charset=utf-8']
        );
    }

    /**
     * Создаёт XML-ответ с корректным заголовком Content-Type.
     *
     * @param string $content Готовое XML-содержимое.
     * @param int    $status  HTTP-статус ответа.
     */
    public static function xml(string $content, int $status = 200): self
    {
        return new self(
            $content,
            $status,
            ['Content-Type' => 'application/xml; charset=utf-8']
        );
    }

    /** Возвращает HTTP-статус ответа. */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** Возвращает тело ответа. */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Возвращает заголовки ответа.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Отправляет HTTP-статус, заголовки и тело клиенту.
     */
    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->content;
    }
}
