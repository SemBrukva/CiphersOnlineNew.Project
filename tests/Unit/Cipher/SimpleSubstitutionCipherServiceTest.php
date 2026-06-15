<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\SimpleSubstitutionCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра простой замены.
 */
final class SimpleSubstitutionCipherServiceTest extends TestCase
{
    private const string EN_KEY = 'QWERTYUIOPASDFGHJKLZXCVBNM';

    private SimpleSubstitutionCipherService $service;

    protected function setUp(): void
    {
        $this->service = new SimpleSubstitutionCipherService();
    }

    // -----------------------------------------------------------------------
    // process() — базовое шифрование/расшифрование
    // -----------------------------------------------------------------------

    /**
     * Проверяет классический пример шифрования.
     * a→q h(7)→i, e(4)→t, l(11)→s, o(14)→g → HELLO = ITSSG
     */
    public function testEncryptsEnglishText(): void
    {
        self::assertSame('ITSSG', $this->service->process('HELLO', 'en', self::EN_KEY, 'encrypt'));
    }

    /**
     * Проверяет расшифрование классического примера.
     */
    public function testDecryptsEnglishText(): void
    {
        self::assertSame('HELLO', $this->service->process('ITSSG', 'en', self::EN_KEY, 'decrypt'));
    }

    /**
     * Проверяет, что encrypt → decrypt возвращает исходный текст.
     */
    public function testRoundtripRestoresOriginal(): void
    {
        $original  = 'The Quick Brown Fox';
        $encrypted = $this->service->process($original, 'en', self::EN_KEY, 'encrypt');
        $decrypted = $this->service->process($encrypted, 'en', self::EN_KEY, 'decrypt');

        self::assertNotSame($original, $encrypted);
        self::assertSame($original, $decrypted);
    }

    /**
     * Проверяет сохранение регистра: верхний регистр на входе → верхний регистр на выходе.
     */
    public function testPreservesUppercase(): void
    {
        $result = $this->service->process('HELLO', 'en', self::EN_KEY, 'encrypt');

        self::assertSame(strtoupper($result), $result);
    }

    /**
     * Проверяет сохранение регистра: нижний регистр на входе → нижний регистр на выходе.
     */
    public function testPreservesLowercase(): void
    {
        $result = $this->service->process('hello', 'en', self::EN_KEY, 'encrypt');

        self::assertSame(strtolower($result), $result);
    }

    /**
     * Проверяет смешанный регистр и знаки препинания в одном тексте.
     */
    public function testPreservesCaseAndPunctuation(): void
    {
        self::assertSame('Itssg, Vgksr!', $this->service->process('Hello, World!', 'en', self::EN_KEY, 'encrypt'));
    }

    /**
     * Проверяет, что символы вне алфавита (цифры, пробелы, знаки) передаются без изменений.
     */
    public function testPassesThroughNonAlphabetChars(): void
    {
        self::assertSame('123 !@#', $this->service->process('123 !@#', 'en', self::EN_KEY, 'encrypt'));
    }

    /**
     * Проверяет, что пустой текст возвращается пустой строкой.
     */
    public function testEmptyTextReturnsEmptyString(): void
    {
        self::assertSame('', $this->service->process('', 'en', self::EN_KEY, 'encrypt'));
    }

    /**
     * Проверяет, что ключ в верхнем регистре нормализуется и даёт тот же результат, что ключ в нижнем.
     */
    public function testUppercaseKeyProducesSameResultAsLowercase(): void
    {
        $lower  = $this->service->process('HELLO', 'en', strtolower(self::EN_KEY), 'encrypt');
        $upper  = $this->service->process('HELLO', 'en', self::EN_KEY, 'encrypt');

        self::assertSame($lower, $upper);
    }

    /**
     * Проверяет шифрование/расшифрование на русском алфавите (roundtrip).
     * Ключ — сдвиг на 1: а→б, б→в, ..., я→а.
     */
    public function testRoundtripRussianAlphabet(): void
    {
        $key       = 'бвгдеёжзийклмнопрстуфхцчшщъыьэюяа';
        $original  = 'Привет, мир!';
        $encrypted = $this->service->process($original, 'ru', $key, 'encrypt');
        $decrypted = $this->service->process($encrypted, 'ru', $key, 'decrypt');

        self::assertNotSame($original, $encrypted);
        self::assertSame($original, $decrypted);
    }

    /**
     * Проверяет корректное шифрование конкретной буквы русского алфавита.
     * Ключ-сдвиг: а→б, ..., я→а. Буква 'м' (индекс 12) → ключ[12]='н'.
     */
    public function testEncryptsRussianLetterCorrectly(): void
    {
        $key = 'бвгдеёжзийклмнопрстуфхцчшщъыьэюяа';

        self::assertSame('нйс', $this->service->process('мир', 'ru', $key, 'encrypt'));
    }

    /**
     * Проверяет, что текст, состоящий только из символов не алфавита, возвращается без изменений.
     */
    public function testPureNonAlphabetTextReturnedUnchanged(): void
    {
        self::assertSame('   —  ...', $this->service->process('   —  ...', 'en', self::EN_KEY, 'encrypt'));
    }

    // -----------------------------------------------------------------------
    // detectAlphabetFromKey()
    // -----------------------------------------------------------------------

    /**
     * Проверяет определение английского алфавита по ключу в верхнем регистре.
     */
    public function testDetectsEnglishAlphabetFromUppercaseKey(): void
    {
        self::assertSame('en', $this->service->detectAlphabetFromKey(self::EN_KEY));
    }

    /**
     * Проверяет определение английского алфавита по ключу в нижнем регистре.
     */
    public function testDetectsEnglishAlphabetFromLowercaseKey(): void
    {
        self::assertSame('en', $this->service->detectAlphabetFromKey(strtolower(self::EN_KEY)));
    }

    /**
     * Проверяет определение русского алфавита.
     */
    public function testDetectsRussianAlphabet(): void
    {
        $key = 'бвгдеёжзийклмнопрстуфхцчшщъыьэюяа';

        self::assertSame('ru', $this->service->detectAlphabetFromKey($key));
    }

    /**
     * Проверяет, что ключ с повторяющимися буквами не является допустимой перестановкой.
     * AACDEFGHIJKLMNOPQRSTUVWXYZ — 'a' встречается дважды, 'b' отсутствует.
     */
    public function testReturnsNullForKeyWithRepeatedLetters(): void
    {
        self::assertNull($this->service->detectAlphabetFromKey('AACDEFGHIJKLMNOPQRSTUVWXYZ'));
    }

    /**
     * Проверяет, что слишком короткий ключ не распознаётся.
     */
    public function testReturnsNullForShortKey(): void
    {
        self::assertNull($this->service->detectAlphabetFromKey('ABCDEFGHIJ'));
    }

    /**
     * Проверяет, что пустой ключ возвращает null.
     */
    public function testReturnsNullForEmptyKey(): void
    {
        self::assertNull($this->service->detectAlphabetFromKey(''));
    }

    /**
     * Проверяет, что ключ с цифрами и спецсимволами не распознаётся ни как один алфавит.
     */
    public function testReturnsNullForKeyWithDigitsAndSymbols(): void
    {
        self::assertNull($this->service->detectAlphabetFromKey('12345678901234567890123456'));
    }

    /**
     * Проверяет, что ключ с одной лишней буквой (27 символов для EN) не распознаётся.
     */
    public function testReturnsNullForKeyOfWrongLength(): void
    {
        self::assertNull($this->service->detectAlphabetFromKey(self::EN_KEY . 'A'));
    }

    // -----------------------------------------------------------------------
    // textContainsAlphabetChars()
    // -----------------------------------------------------------------------

    /**
     * Проверяет, что текст с латинскими буквами определяется как содержащий английский алфавит.
     */
    public function testReturnsTrueForEnglishTextWithEnAlphabet(): void
    {
        self::assertTrue($this->service->textContainsAlphabetChars('Hello World', 'en'));
    }

    /**
     * Проверяет нечувствительность к регистру: текст в верхнем регистре.
     */
    public function testReturnsTrueForUppercaseEnglishText(): void
    {
        self::assertTrue($this->service->textContainsAlphabetChars('HELLO', 'en'));
    }

    /**
     * Проверяет, что русский текст не содержит символов английского алфавита.
     */
    public function testReturnsFalseForRussianTextWithEnAlphabet(): void
    {
        self::assertFalse($this->service->textContainsAlphabetChars('Привет мир', 'en'));
    }

    /**
     * Проверяет, что текст из одних цифр и знаков не содержит символов алфавита.
     */
    public function testReturnsFalseForPureDigitsAndSymbols(): void
    {
        self::assertFalse($this->service->textContainsAlphabetChars('123 !@# 456', 'en'));
    }

    /**
     * Проверяет, что пустой текст возвращает false.
     */
    public function testReturnsFalseForEmptyText(): void
    {
        self::assertFalse($this->service->textContainsAlphabetChars('', 'en'));
    }

    /**
     * Проверяет, что русский текст содержит символы русского алфавита.
     */
    public function testReturnsTrueForRussianTextWithRuAlphabet(): void
    {
        self::assertTrue($this->service->textContainsAlphabetChars('Привет', 'ru'));
    }

    /**
     * Проверяет mixed-текст: латиница + кириллица + знаки.
     * Для английского алфавита достаточно одной латинской буквы.
     */
    public function testReturnsTrueForMixedTextWhenAlphabetCharPresent(): void
    {
        self::assertTrue($this->service->textContainsAlphabetChars('Привет A мир!', 'en'));
        self::assertTrue($this->service->textContainsAlphabetChars('Hello Мир 123', 'ru'));
    }

    /**
     * Проверяет, что текст из одних пробелов не содержит символов алфавита.
     */
    public function testReturnsFalseForWhitespaceOnly(): void
    {
        self::assertFalse($this->service->textContainsAlphabetChars('   ', 'en'));
    }
}
