<?php

declare(strict_types=1);

namespace App\I18n;

/**
 * Правила определения форм множественного числа по локали.
 *
 * Реализует подмножество стандарта Unicode CLDR Plural Rules.
 * Поддерживаются: английский, немецкий, испанский и другие (2 формы),
 * русский, украинский, белорусский, польский, чешский, словацкий (3 формы),
 * французский, турецкий, японский, китайский и другие (1 форма).
 */
final class PluralRules
{
    /**
     * Возвращает 0-based индекс формы для pipe-разделённых строк выбора.
     *
     * Порядок форм в строке перевода зависит от языка:
     *   - 2 формы (en, de, es, …): one | other
     *   - 3 формы (ru, uk, be, pl, cs, sk): one | few | many
     *   - 1 форма (fr, tr, ja, zh, …): other
     *
     * @param string    $locale Код локали (en, ru, pl, zh_CN, …).
     * @param int|float $count  Число, для которого нужна форма.
     */
    public static function select(string $locale, int|float $count): int
    {
        $n    = abs((int) $count);
        $lang = self::language($locale);

        return match ($lang) {
            'ru', 'uk', 'be' => self::slavicIndex($n),
            'pl'             => self::polishIndex($n),
            'cs', 'sk'       => self::czechIndex($n),
            'fr', 'pt', 'tr',
            'id', 'hu', 'ko',
            'ja', 'zh'       => 0,
            default          => $n === 1 ? 0 : 1,
        };
    }

    /**
     * Возвращает CLDR-категорию множественного числа (zero/one/few/many/other).
     *
     * Используется при разборе ICU-паттернов вида {n, plural, one {…} other {…}}.
     *
     * @param string    $locale Код локали.
     * @param int|float $count  Число.
     */
    public static function category(string $locale, int|float $count): string
    {
        $n    = abs((int) $count);
        $lang = self::language($locale);

        return match ($lang) {
            'ru', 'uk', 'be' => self::slavicCategory($n),
            'pl'             => self::polishCategory($n),
            'cs', 'sk'       => self::czechCategory($n),
            'fr', 'pt', 'tr',
            'id', 'hu', 'ko',
            'ja', 'zh'       => 'other',
            default          => $n === 1 ? 'one' : 'other',
        };
    }

    // ── Восточнославянские (ru, uk, be) ──────────────────────────────────────

    private static function slavicIndex(int $n): int
    {
        return match (self::slavicCategory($n)) {
            'one'   => 0,
            'few'   => 1,
            default => 2,
        };
    }

    private static function slavicCategory(int $n): string
    {
        if ($n % 10 === 1 && $n % 100 !== 11) {
            return 'one';
        }
        if ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) {
            return 'few';
        }
        return 'many';
    }

    // ── Польский ─────────────────────────────────────────────────────────────

    private static function polishIndex(int $n): int
    {
        return match (self::polishCategory($n)) {
            'one'   => 0,
            'few'   => 1,
            default => 2,
        };
    }

    private static function polishCategory(int $n): string
    {
        if ($n === 1) {
            return 'one';
        }
        if ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) {
            return 'few';
        }
        return 'many';
    }

    // ── Чешский / Словацкий ──────────────────────────────────────────────────

    private static function czechIndex(int $n): int
    {
        return match (self::czechCategory($n)) {
            'one'   => 0,
            'few'   => 1,
            default => 2,
        };
    }

    private static function czechCategory(int $n): string
    {
        if ($n === 1) {
            return 'one';
        }
        if ($n >= 2 && $n <= 4) {
            return 'few';
        }
        return 'other';
    }

    // ── Утилита ──────────────────────────────────────────────────────────────

    /**
     * Извлекает двухбуквенный код языка из локали (en_US → en, zh-CN → zh).
     */
    private static function language(string $locale): string
    {
        return strtolower(explode('_', str_replace('-', '_', $locale))[0]);
    }
}
