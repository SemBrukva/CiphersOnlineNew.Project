<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента Base45 по RFC 9285 (кодирование/декодирование в браузере).
 */
final readonly class Base45CipherService
{
    /**
     * Возвращает UI-настройки инструмента. У Base45 единственный вариант (RFC 9285),
     * поэтому select не нужен.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [];
    }

    /**
     * Возвращает элементы блока доверия.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('BASE45_TRUST_PURPOSE'),
            trans('BASE45_TRUST_USES'),
            trans('CIPHER_TOOL_TRUST_UTF8'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
