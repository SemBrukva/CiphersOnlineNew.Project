<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента HTML encode / decode.
 */
final readonly class HtmlEncodeCipherService
{
    /**
     * Возвращает UI-настройки инструмента.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [];
    }

    /**
     * Возвращает элементы блока доверия для HTML encode / decode.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('HTML_TRUST_PURPOSE'),
            trans('HTML_TRUST_ENTITIES'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
