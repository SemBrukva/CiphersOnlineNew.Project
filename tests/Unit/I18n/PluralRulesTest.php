<?php

declare(strict_types=1);

namespace Tests\Unit\I18n;

use App\I18n\PluralRules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет правила выбора форм множественного числа для разных локалей.
 */
final class PluralRulesTest extends TestCase
{
    // ── PluralRules::select() ─────────────────────────────────────────────────

    /**
     * Проверяет индексы форм для английского (2 формы: 0=one, 1=other).
     */
    #[DataProvider('englishSelectProvider')]
    public function testSelectEnglish(int $n, int $expected): void
    {
        self::assertSame($expected, PluralRules::select('en', $n));
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function englishSelectProvider(): array
    {
        return [
            'zero'      => [0, 1],
            'one'       => [1, 0],
            'two'       => [2, 1],
            'eleven'    => [11, 1],
            'twentyone' => [21, 1],
            'hundred'   => [100, 1],
        ];
    }

    /**
     * Проверяет индексы форм для русского (3 формы: 0=one, 1=few, 2=many).
     */
    #[DataProvider('russianSelectProvider')]
    public function testSelectRussian(int $n, int $expected): void
    {
        self::assertSame($expected, PluralRules::select('ru', $n));
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function russianSelectProvider(): array
    {
        return [
            'zero'           => [0, 2],
            'one'            => [1, 0],
            'two'            => [2, 1],
            'three'          => [3, 1],
            'four'           => [4, 1],
            'five'           => [5, 2],
            'ten'            => [10, 2],
            'eleven'         => [11, 2],
            'twelve'         => [12, 2],
            'twenty_one'     => [21, 0],
            'twenty_two'     => [22, 1],
            'twenty_five'    => [25, 2],
            'hundred_one'    => [101, 0],
            'hundred_eleven' => [111, 2],
        ];
    }

    /**
     * Проверяет, что локаль «ru_RU» (с суффиксом) работает так же, как «ru».
     */
    public function testSelectRussianWithRegion(): void
    {
        self::assertSame(PluralRules::select('ru', 1), PluralRules::select('ru_RU', 1));
        self::assertSame(PluralRules::select('ru', 5), PluralRules::select('ru_RU', 5));
    }

    /**
     * Проверяет, что для французского всегда возвращается 0 (1 форма).
     */
    public function testSelectFrenchAlwaysZero(): void
    {
        foreach ([0, 1, 2, 5, 11, 100] as $n) {
            self::assertSame(0, PluralRules::select('fr', $n), "fr: n=$n");
        }
    }

    /**
     * Проверяет польский (3 формы, но с другим правилом для 1).
     */
    #[DataProvider('polishSelectProvider')]
    public function testSelectPolish(int $n, int $expected): void
    {
        self::assertSame($expected, PluralRules::select('pl', $n));
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function polishSelectProvider(): array
    {
        return [
            'one'         => [1, 0],
            'two'         => [2, 1],
            'four'        => [4, 1],
            'five'        => [5, 2],
            'twelve'      => [12, 2],
            'twenty_two'  => [22, 1],
            'twenty_five' => [25, 2],
        ];
    }

    // ── PluralRules::category() ───────────────────────────────────────────────

    /**
     * Проверяет CLDR-категории для английского.
     */
    #[DataProvider('englishCategoryProvider')]
    public function testCategoryEnglish(int $n, string $expected): void
    {
        self::assertSame($expected, PluralRules::category('en', $n));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function englishCategoryProvider(): array
    {
        return [
            'zero'       => [0, 'other'],
            'one'        => [1, 'one'],
            'two'        => [2, 'other'],
            'five'       => [5, 'other'],
            'twenty_one' => [21, 'other'],
        ];
    }

    /**
     * Проверяет CLDR-категории для русского.
     */
    #[DataProvider('russianCategoryProvider')]
    public function testCategoryRussian(int $n, string $expected): void
    {
        self::assertSame($expected, PluralRules::category('ru', $n));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function russianCategoryProvider(): array
    {
        return [
            'zero'           => [0, 'many'],
            'one'            => [1, 'one'],
            'two'            => [2, 'few'],
            'four'           => [4, 'few'],
            'five'           => [5, 'many'],
            'eleven'         => [11, 'many'],
            'twenty_one'     => [21, 'one'],
            'twenty_two'     => [22, 'few'],
            'hundred_eleven' => [111, 'many'],
        ];
    }

    /**
     * Проверяет, что отрицательные числа обрабатываются через abs().
     */
    public function testNegativeCountUsesAbsoluteValue(): void
    {
        self::assertSame(PluralRules::select('ru', 1), PluralRules::select('ru', -1));
        self::assertSame(PluralRules::select('ru', 2), PluralRules::select('ru', -2));
        self::assertSame(PluralRules::select('en', 1), PluralRules::select('en', -1));
    }
}
