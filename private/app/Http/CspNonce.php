<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Генерирует и хранит криптографически случайный nonce для CSP.
 *
 * Создаётся контейнером как синглтон — nonce генерируется один раз за запрос
 * и используется совместно в SecurityHeadersMiddleware и шаблонизаторе.
 */
final class CspNonce
{
    private ?string $nonce = null;

    /**
     * Возвращает nonce текущего запроса, генерируя его при первом обращении.
     */
    public function get(): string
    {
        $this->nonce ??= base64_encode(random_bytes(16));
        return $this->nonce;
    }
}
