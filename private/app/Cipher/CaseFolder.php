<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Локально-чувствительное преобразование регистра для алфавитов, где стандартный
 * Unicode case-fold (`mb_strtolower` / `mb_strtoupper` без локали) даёт неверные пары.
 *
 * Главный случай — турецкий: пары I↔ı и İ↔i отличаются от латинской I↔i. Без
 * корректировки `mb_strtolower('I')` даёт точечную 'i' вместо беспрочечной 'ı',
 * а `mb_strtoupper('i')` даёт беспрочечную 'I' вместо точечной 'İ' — это ломает
 * round-trip любого шифра, чьи координаты зависят от позиции буквы в алфавите.
 *
 * Решение: для языков из карты мы заранее подменяем «спорные» символы на их
 * целевую форму, а затем применяем обычный `mb_str(to)*`. После подмены символы
 * уже соответствуют целевому регистру, поэтому стандартная функция их не трогает.
 */
final readonly class CaseFolder
{
    /**
     * Карта замен перед `mb_strtolower` (источник → корректный нижний регистр).
     *
     * @var array<string, array<string, string>>
     */
    private const array LOWER_MAP = [
        'tr' => [
            'I' => 'ı',
            'İ' => 'i',
        ],
    ];

    /**
     * Карта замен перед `mb_strtoupper` (источник → корректный верхний регистр).
     *
     * @var array<string, array<string, string>>
     */
    private const array UPPER_MAP = [
        'tr' => [
            'ı' => 'I',
            'i' => 'İ',
        ],
    ];

    /**
     * Возвращает текст в нижнем регистре с учётом локали алфавита.
     */
    public function toLower(string $text, string $alphabet): string
    {
        if (isset(self::LOWER_MAP[$alphabet])) {
            $text = strtr($text, self::LOWER_MAP[$alphabet]);
        }

        return mb_strtolower($text, 'UTF-8');
    }

    /**
     * Возвращает текст в верхнем регистре с учётом локали алфавита.
     */
    public function toUpper(string $text, string $alphabet): string
    {
        if (isset(self::UPPER_MAP[$alphabet])) {
            $text = strtr($text, self::UPPER_MAP[$alphabet]);
        }

        return mb_strtoupper($text, 'UTF-8');
    }
}
