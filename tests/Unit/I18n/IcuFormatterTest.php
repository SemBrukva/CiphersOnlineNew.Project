<?php

declare(strict_types=1);

namespace Tests\Unit\I18n;

use App\I18n\IcuFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет разбор и подстановку ICU MessageFormat-паттернов.
 */
final class IcuFormatterTest extends TestCase
{
    // ── Простые переменные ────────────────────────────────────────────────────

    /**
     * Проверяет подстановку одиночной переменной {name}.
     */
    public function testSimpleVariableSubstitution(): void
    {
        $result = IcuFormatter::format('Hello, {name}!', 'en', ['name' => 'World']);
        self::assertSame('Hello, World!', $result);
    }

    /**
     * Проверяет подстановку нескольких переменных в одной строке.
     */
    public function testMultipleVariables(): void
    {
        $result = IcuFormatter::format('{greeting}, {name}!', 'en', [
            'greeting' => 'Hi',
            'name'     => 'Alice',
        ]);
        self::assertSame('Hi, Alice!', $result);
    }

    /**
     * Проверяет, что неизвестная переменная возвращается в исходном виде {var}.
     */
    public function testMissingVariableReturnedAsIs(): void
    {
        $result = IcuFormatter::format('Hello, {name}!', 'en', []);
        self::assertSame('Hello, {name}!', $result);
    }

    /**
     * Проверяет подстановку целочисленного значения.
     */
    public function testIntegerVariableSubstitution(): void
    {
        $result = IcuFormatter::format('Count: {n}', 'en', ['n' => 42]);
        self::assertSame('Count: 42', $result);
    }

    // ── Plural (английский) ───────────────────────────────────────────────────

    /**
     * Проверяет выбор формы «one» для n=1 в английском.
     */
    public function testPluralEnglishOne(): void
    {
        $result = IcuFormatter::format(
            'You have {count, plural, one {# message} other {# messages}}.',
            'en',
            ['count' => 1],
        );
        self::assertSame('You have 1 message.', $result);
    }

    /**
     * Проверяет выбор формы «other» для n=5 в английском.
     */
    public function testPluralEnglishOther(): void
    {
        $result = IcuFormatter::format(
            'You have {count, plural, one {# message} other {# messages}}.',
            'en',
            ['count' => 5],
        );
        self::assertSame('You have 5 messages.', $result);
    }

    /**
     * Проверяет точное совпадение =0 (имеет приоритет над категорией).
     */
    public function testPluralExactMatchZero(): void
    {
        $result = IcuFormatter::format(
            '{count, plural, =0 {no messages} one {# message} other {# messages}}',
            'en',
            ['count' => 0],
        );
        self::assertSame('no messages', $result);
    }

    /**
     * Проверяет, что =1 имеет приоритет над категорией «one».
     */
    public function testPluralExactMatchOne(): void
    {
        $result = IcuFormatter::format(
            '{count, plural, =1 {exactly one} one {# item} other {# items}}',
            'en',
            ['count' => 1],
        );
        self::assertSame('exactly one', $result);
    }

    // ── Plural (русский) ──────────────────────────────────────────────────────

    /**
     * Проверяет форму «one» для n=1 в русском.
     */
    public function testPluralRussianOne(): void
    {
        $result = IcuFormatter::format(
            '{n, plural, one {# сообщение} few {# сообщения} many {# сообщений} other {# сообщения}}',
            'ru',
            ['n' => 1],
        );
        self::assertSame('1 сообщение', $result);
    }

    /**
     * Проверяет форму «few» для n=3 в русском.
     */
    public function testPluralRussianFew(): void
    {
        $result = IcuFormatter::format(
            '{n, plural, one {# сообщение} few {# сообщения} many {# сообщений} other {# сообщения}}',
            'ru',
            ['n' => 3],
        );
        self::assertSame('3 сообщения', $result);
    }

    /**
     * Проверяет форму «many» для n=5 в русском.
     */
    public function testPluralRussianMany(): void
    {
        $result = IcuFormatter::format(
            '{n, plural, one {# сообщение} few {# сообщения} many {# сообщений} other {# сообщения}}',
            'ru',
            ['n' => 5],
        );
        self::assertSame('5 сообщений', $result);
    }

    /**
     * Проверяет форму для n=11 («many» для русского).
     */
    public function testPluralRussianEleven(): void
    {
        $result = IcuFormatter::format(
            '{n, plural, one {# сообщение} few {# сообщения} many {# сообщений} other {# сообщения}}',
            'ru',
            ['n' => 11],
        );
        self::assertSame('11 сообщений', $result);
    }

    /**
     * Проверяет форму для n=21 («one» для русского).
     */
    public function testPluralRussianTwentyOne(): void
    {
        $result = IcuFormatter::format(
            '{n, plural, one {# сообщение} few {# сообщения} many {# сообщений} other {# сообщения}}',
            'ru',
            ['n' => 21],
        );
        self::assertSame('21 сообщение', $result);
    }

    // ── Plural: обратный путь к «other» ──────────────────────────────────────

    /**
     * Проверяет, что при отсутствии нужной категории используется «other».
     */
    public function testPluralFallsBackToOther(): void
    {
        $result = IcuFormatter::format(
            '{n, plural, other {# things}}',
            'en',
            ['n' => 1],
        );
        self::assertSame('1 things', $result);
    }

    // ── Select ────────────────────────────────────────────────────────────────

    /**
     * Проверяет выбор по строковому значению (male).
     */
    public function testSelectMale(): void
    {
        $result = IcuFormatter::format(
            '{gender, select, male {He} female {She} other {They}} arrived.',
            'en',
            ['gender' => 'male'],
        );
        self::assertSame('He arrived.', $result);
    }

    /**
     * Проверяет выбор по строковому значению (female).
     */
    public function testSelectFemale(): void
    {
        $result = IcuFormatter::format(
            '{gender, select, male {He} female {She} other {They}} arrived.',
            'en',
            ['gender' => 'female'],
        );
        self::assertSame('She arrived.', $result);
    }

    /**
     * Проверяет fallback к «other» в select для неизвестного значения.
     */
    public function testSelectFallsBackToOther(): void
    {
        $result = IcuFormatter::format(
            '{gender, select, male {He} female {She} other {They}} arrived.',
            'en',
            ['gender' => 'nonbinary'],
        );
        self::assertSame('They arrived.', $result);
    }

    // ── Вложенные паттерны ────────────────────────────────────────────────────

    /**
     * Проверяет plural с вложенной переменной внутри кейса.
     */
    public function testPluralWithNestedVariable(): void
    {
        $result = IcuFormatter::format(
            '{count, plural, one {One {type}} other {# {type}s}}',
            'en',
            ['count' => 3, 'type' => 'apple'],
        );
        self::assertSame('3 apples', $result);
    }

    /**
     * Проверяет plural с вложенной переменной для n=1.
     */
    public function testPluralWithNestedVariableOne(): void
    {
        $result = IcuFormatter::format(
            '{count, plural, one {One {type}} other {# {type}s}}',
            'en',
            ['count' => 1, 'type' => 'apple'],
        );
        self::assertSame('One apple', $result);
    }

    // ── Строки без плейсхолдеров ──────────────────────────────────────────────

    /**
     * Проверяет, что строка без плейсхолдеров возвращается без изменений.
     */
    public function testPlainStringPassesThrough(): void
    {
        $result = IcuFormatter::format('No placeholders here.', 'en', []);
        self::assertSame('No placeholders here.', $result);
    }
}
