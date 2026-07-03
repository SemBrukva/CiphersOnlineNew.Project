<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента сравнения текстов (Text Diff).
 *
 * Инструмент полностью клиентский и использует собственный двухпанельный шаблон,
 * поэтому набор generic-настроек пуст — управляющие элементы описаны прямо в виджете.
 */
final readonly class TextDiffService
{
    /**
     * Возвращает generic UI-настройки инструмента. Для Text Diff их нет.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [];
    }

    /**
     * Возвращает элементы блока доверия для инструмента сравнения текстов.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('DIFF_TRUST_CLIENT'),
            trans('DIFF_TRUST_GRANULAR'),
            trans('DIFF_TRUST_INSTANT'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
