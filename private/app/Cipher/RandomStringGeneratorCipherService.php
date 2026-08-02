<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента «Генератор случайных строк».
 *
 * Генерация выполняется целиком на клиенте (calculation_mode='client') собственным
 * виджетом (pages/randomstring-generator.js), поэтому стандартные настройки не
 * используются — возвращается пустой набор.
 */
final readonly class RandomStringGeneratorCipherService
{
    /**
     * Возвращает UI-настройки инструмента. Все элементы управления (длина, наборы
     * символов, произвольный алфавит, формат вывода) рендерит собственный шаблон
     * `_randomstring_widget.tpl`, поэтому набор пуст.
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
            trans('RS_TRUST_PURPOSE'),
            trans('RS_TRUST_USES'),
            trans('RS_TRUST_SECURE'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
