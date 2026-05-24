<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Контекст текущего HTTP-запроса.
 *
 * Хранит стабильные метаданные запроса, которые раньше брались из $_SERVER.
 */
final readonly class RequestContext
{
    /**
     * Создаёт контекст запроса.
     */
    public function __construct(
        public string $requestId,
        public float $startedAt,
        public bool $isApi
    ) {
    }

    /**
     * Возвращает смещение в миллисекундах от старта запроса до $timestamp.
     */
    public function offsetMs(float $timestamp): float
    {
        return round(($timestamp - $this->startedAt) * 1000, 3);
    }

    /**
     * Возвращает текущее время выполнения запроса в миллисекундах.
     */
    public function elapsedMs(): float
    {
        return round((microtime(true) - $this->startedAt) * 1000, 3);
    }
}
