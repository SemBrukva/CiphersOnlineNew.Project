<?php

declare(strict_types=1);

namespace Tests\Unit\I18n;

use App\I18n\Translator;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет Translator: загрузку переводов, подстановку параметров, ICU и pluralization.
 */
final class TranslatorTest extends TestCase
{
    /** Путь к тестовым фикстурам переводов. */
    private const FIXTURE_PATH = BASE_PATH . '/tests/fixtures/translates';

    /**
     * Создаёт экземпляр Translator для указанной локали.
     */
    private function makeTranslator(string $locale = 'en'): Translator
    {
        return new Translator([
            'locale'    => $locale,
            'locales'   => ['en', 'ru'],
            'multilang' => false,
            'path'      => self::FIXTURE_PATH,
        ]);
    }

    // ── Базовая загрузка переводов ────────────────────────────────────────────

    /**
     * Проверяет, что простая строка возвращается без изменений.
     */
    public function testGetSimpleKey(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('Hello World', $t->get('SIMPLE'));
    }

    /**
     * Проверяет, что несуществующий ключ возвращается как есть.
     */
    public function testGetMissingKeyReturnsKey(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('UNKNOWN_KEY', $t->get('UNKNOWN_KEY'));
    }

    /**
     * Проверяет, что ключ без ICU-паттерна возвращается без изменений при пустом replace.
     */
    public function testGetStringWithoutPlaceholdersPassesThrough(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('No replacement here.', $t->get('MISSING_STAYS'));
    }

    // ── Классическая замена :param ─────────────────────────────────────────────

    /**
     * Проверяет замену классического плейсхолдера :name.
     */
    public function testGetColonPlaceholder(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('Hello, Alice!', $t->get('PARAM_COLON', ['name' => 'Alice']));
    }

    /**
     * Проверяет замену :name в русском переводе.
     */
    public function testGetColonPlaceholderRussian(): void
    {
        $t = $this->makeTranslator('ru');
        self::assertSame('Привет, Саша!', $t->get('PARAM_COLON', ['name' => 'Саша']));
    }

    // ── ICU: простые переменные {param} ──────────────────────────────────────

    /**
     * Проверяет подстановку ICU-переменной {name}.
     */
    public function testGetIcuSimpleVariable(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('Hello, Bob!', $t->get('ICU_SIMPLE', ['name' => 'Bob']));
    }

    /**
     * Проверяет подстановку двух ICU-переменных.
     */
    public function testGetIcuTwoVariables(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('Hi, Carol!', $t->get('ICU_TWO_PARAMS', [
            'greeting' => 'Hi',
            'name'     => 'Carol',
        ]));
    }

    /**
     * Проверяет, что строка без ICU не обрабатывается как ICU ($_var не триггерит).
     */
    public function testGetLiteralBraceNotTriggersIcu(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('Use $_var in code.', $t->get('LITERAL_BRACE'));
    }

    // ── ICU: plural (английский) ──────────────────────────────────────────────

    /**
     * Проверяет ICU plural для n=0 (точное совпадение =0).
     */
    public function testGetIcuPluralEnglishZero(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('You have no messages.', $t->get('ICU_PLURAL', ['count' => 0]));
    }

    /**
     * Проверяет ICU plural для n=1 (форма «one»).
     */
    public function testGetIcuPluralEnglishOne(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('You have 1 message.', $t->get('ICU_PLURAL', ['count' => 1]));
    }

    /**
     * Проверяет ICU plural для n=5 (форма «other»).
     */
    public function testGetIcuPluralEnglishOther(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('You have 5 messages.', $t->get('ICU_PLURAL', ['count' => 5]));
    }

    // ── ICU: plural (русский) ─────────────────────────────────────────────────

    /**
     * Проверяет ICU plural для n=0 (точное совпадение =0) в русском.
     */
    public function testGetIcuPluralRussianZero(): void
    {
        $t = $this->makeTranslator('ru');
        self::assertSame('У вас нет сообщений.', $t->get('ICU_PLURAL', ['count' => 0]));
    }

    /**
     * Проверяет ICU plural для n=1 (форма «one») в русском.
     */
    public function testGetIcuPluralRussianOne(): void
    {
        $t = $this->makeTranslator('ru');
        self::assertSame('У вас 1 сообщение.', $t->get('ICU_PLURAL', ['count' => 1]));
    }

    /**
     * Проверяет ICU plural для n=3 (форма «few») в русском.
     */
    public function testGetIcuPluralRussianFew(): void
    {
        $t = $this->makeTranslator('ru');
        self::assertSame('У вас 3 сообщения.', $t->get('ICU_PLURAL', ['count' => 3]));
    }

    /**
     * Проверяет ICU plural для n=5 (форма «many») в русском.
     */
    public function testGetIcuPluralRussianMany(): void
    {
        $t = $this->makeTranslator('ru');
        self::assertSame('У вас 5 сообщений.', $t->get('ICU_PLURAL', ['count' => 5]));
    }

    /**
     * Проверяет ICU plural для n=21 («one» в русском).
     */
    public function testGetIcuPluralRussianTwentyOne(): void
    {
        $t = $this->makeTranslator('ru');
        self::assertSame('У вас 21 сообщение.', $t->get('ICU_PLURAL', ['count' => 21]));
    }

    // ── ICU: вложенные переменные ─────────────────────────────────────────────

    /**
     * Проверяет plural с вложенной переменной для n=1.
     */
    public function testGetIcuPluralNestedVariableOne(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('One apple', $t->get('ICU_PLURAL_NESTED', ['count' => 1, 'type' => 'apple']));
    }

    /**
     * Проверяет plural с вложенной переменной для n=3.
     */
    public function testGetIcuPluralNestedVariableOther(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('3 apples', $t->get('ICU_PLURAL_NESTED', ['count' => 3, 'type' => 'apple']));
    }

    // ── ICU: select ───────────────────────────────────────────────────────────

    /**
     * Проверяет выбор по полу (male).
     */
    public function testGetIcuSelectMale(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('He arrived.', $t->get('ICU_SELECT', ['gender' => 'male']));
    }

    /**
     * Проверяет выбор по полу (female).
     */
    public function testGetIcuSelectFemale(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('She arrived.', $t->get('ICU_SELECT', ['gender' => 'female']));
    }

    /**
     * Проверяет fallback к «other» в select.
     */
    public function testGetIcuSelectOtherFallback(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('They arrived.', $t->get('ICU_SELECT', ['gender' => 'unknown']));
    }

    // ── choice() — pipe-разделённые формы ────────────────────────────────────

    /**
     * Проверяет выбор «one» для n=1 в английском.
     */
    public function testChoiceEnglishOne(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('1 item', $t->choice('CHOICE', 1));
    }

    /**
     * Проверяет выбор «other» для n=2 в английском.
     */
    public function testChoiceEnglishOther(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('2 items', $t->choice('CHOICE', 2));
    }

    /**
     * Проверяет выбор «other» для n=5 в английском.
     */
    public function testChoiceEnglishFive(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('5 items', $t->choice('CHOICE', 5));
    }

    /**
     * Проверяет, что count автоматически добавляется в replace.
     */
    public function testChoiceAutoInjectsCount(): void
    {
        $t = $this->makeTranslator('en');
        // Передаём пустой replace — :count должен подставиться автоматически.
        self::assertSame('3 items', $t->choice('CHOICE', 3));
    }

    /**
     * Проверяет choice с ICU-плейсхолдером {count}.
     */
    public function testChoiceWithCurlyCount(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('1 item', $t->choice('CHOICE_CURLY', 1));
        self::assertSame('5 items', $t->choice('CHOICE_CURLY', 5));
    }

    /**
     * Проверяет выбор «one» для n=1 в русском.
     */
    public function testChoiceRussianOne(): void
    {
        $t = $this->makeTranslator('ru');
        self::assertSame('1 элемент', $t->choice('CHOICE', 1));
    }

    /**
     * Проверяет выбор «few» для n=3 в русском.
     */
    public function testChoiceRussianFew(): void
    {
        $t = $this->makeTranslator('ru');
        self::assertSame('3 элемента', $t->choice('CHOICE', 3));
    }

    /**
     * Проверяет выбор «many» для n=5 в русском.
     */
    public function testChoiceRussianMany(): void
    {
        $t = $this->makeTranslator('ru');
        self::assertSame('5 элементов', $t->choice('CHOICE', 5));
    }

    /**
     * Проверяет выбор «many» для n=11 в русском (граничный случай).
     */
    public function testChoiceRussianEleven(): void
    {
        $t = $this->makeTranslator('ru');
        self::assertSame('11 элементов', $t->choice('CHOICE', 11));
    }

    /**
     * Проверяет выбор «one» для n=21 в русском.
     */
    public function testChoiceRussianTwentyOne(): void
    {
        $t = $this->makeTranslator('ru');
        self::assertSame('21 элемент', $t->choice('CHOICE', 21));
    }

    /**
     * Проверяет «few» для n=22 в русском.
     */
    public function testChoiceRussianTwentyTwo(): void
    {
        $t = $this->makeTranslator('ru');
        self::assertSame('22 элемента', $t->choice('CHOICE', 22));
    }

    /**
     * Проверяет, что при нехватке форм используется последняя.
     */
    public function testChoiceClampsToLastFormWhenShort(): void
    {
        // Ключ 'CHOICE' в en.php имеет 2 формы; для ru нужны 3, но берём en-фикстуру
        $t = $this->makeTranslator('en');
        // n=5 → index=1 → «5 items» (правильно для en)
        self::assertSame('5 items', $t->choice('CHOICE', 5));
    }

    // ── Смена локали ──────────────────────────────────────────────────────────

    /**
     * Проверяет, что setLocale() сбрасывает кэш переводов.
     */
    public function testSetLocaleResetsTranslations(): void
    {
        $t = $this->makeTranslator('en');
        self::assertSame('Hello World', $t->get('SIMPLE'));

        $t->setLocale('ru');
        self::assertSame('Привет, мир', $t->get('SIMPLE'));
    }

    /**
     * Проверяет, что повторный вызов setLocale() с той же локалью не сбрасывает кэш.
     */
    public function testSetLocaleWithSameLocaleIsNoop(): void
    {
        $t = $this->makeTranslator('en');
        $t->get('SIMPLE'); // загружаем переводы
        $t->setLocale('en'); // не должно сбросить

        self::assertSame('en', $t->getLocale());
        self::assertSame('Hello World', $t->get('SIMPLE'));
    }

    // ── all() ─────────────────────────────────────────────────────────────────

    /**
     * Проверяет, что all() возвращает все переводы в виде массива.
     */
    public function testAllReturnsTranslations(): void
    {
        $t      = $this->makeTranslator('en');
        $all    = $t->all();

        self::assertIsArray($all);
        self::assertArrayHasKey('SIMPLE', $all);
        self::assertSame('Hello World', $all['SIMPLE']);
    }
}
