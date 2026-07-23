<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента «Генератор UUID / GUID».
 *
 * Генерация выполняется целиком на клиенте (calculation_mode='client') собственным
 * виджетом, поэтому стандартные настройки не используются — возвращается пустой набор.
 */
final readonly class UuidGeneratorCipherService
{
    /**
     * Возвращает UI-настройки инструмента. Все элементы управления (версия, количество,
     * формат) рендерит собственный шаблон `_uuid_widget.tpl`, поэтому набор пуст.
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
            trans('UUID_TRUST_PURPOSE'),
            trans('UUID_TRUST_VERSIONS'),
            trans('UUID_TRUST_SECURE'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
