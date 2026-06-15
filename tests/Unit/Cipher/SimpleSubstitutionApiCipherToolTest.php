<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\SimpleSubstitutionApiCipherTool;
use App\Cipher\SimpleSubstitutionCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра простой замены.
 */
final class SimpleSubstitutionApiCipherToolTest extends TestCase
{
    private const string EN_KEY = 'QWERTYUIOPASDFGHJKLZXCVBNM';

    private SimpleSubstitutionApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new SimpleSubstitutionApiCipherTool(new SimpleSubstitutionCipherService());
    }

    // -----------------------------------------------------------------------
    // Метаданные
    // -----------------------------------------------------------------------

    /**
     * Проверяет, что action() возвращает 'simple-substitution'.
     */
    public function testActionReturnsSimpleSubstitution(): void
    {
        self::assertSame('simple-substitution', $this->tool->action());
    }

    // -----------------------------------------------------------------------
    // Успешные сценарии
    // -----------------------------------------------------------------------

    /**
     * Проверяет корректное шифрование с английским ключом.
     */
    public function testEncryptsWithValidEnglishKey(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => self::EN_KEY],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ITSSG', $result['result']);
    }

    /**
     * Проверяет корректное расшифрование с английским ключом.
     */
    public function testDecryptsWithValidEnglishKey(): void
    {
        $result = $this->tool->execute([
            'text'      => 'ITSSG',
            'direction' => 'decrypt',
            'settings'  => ['key' => self::EN_KEY],
        ]);

        self::assertSame('HELLO', $result['result']);
    }

    /**
     * Проверяет, что ответ содержит поле 'alphabet' с кодом обнаруженного алфавита.
     */
    public function testResponseContainsDetectedAlphabetCode(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => self::EN_KEY],
        ]);

        self::assertSame('en', $result['alphabet']);
    }

    /**
     * Проверяет roundtrip для английского текста через API-инструмент.
     */
    public function testRoundtripThroughApiTool(): void
    {
        $encrypted = $this->tool->execute([
            'text' => 'Hello, World!', 'direction' => 'encrypt', 'settings' => ['key' => self::EN_KEY],
        ]);
        $decrypted = $this->tool->execute([
            'text' => $encrypted['result'], 'direction' => 'decrypt', 'settings' => ['key' => self::EN_KEY],
        ]);

        self::assertSame('Hello, World!', $decrypted['result']);
    }

    /**
     * Проверяет шифрование и расшифрование с русским ключом.
     */
    public function testEncryptsAndDecryptsRussianText(): void
    {
        $key = 'бвгдеёжзийклмнопрстуфхцчшщъыьэюяа';

        $encrypted = $this->tool->execute([
            'text' => 'Привет мир', 'direction' => 'encrypt', 'settings' => ['key' => $key],
        ]);
        self::assertSame('ru', $encrypted['alphabet']);

        $decrypted = $this->tool->execute([
            'text' => $encrypted['result'], 'direction' => 'decrypt', 'settings' => ['key' => $key],
        ]);
        self::assertSame('Привет мир', $decrypted['result']);
    }

    /**
     * Проверяет, что ключ в нижнем регистре даёт тот же результат, что в верхнем.
     */
    public function testLowercaseKeyProducesSameResult(): void
    {
        $upper = $this->tool->execute([
            'text' => 'HELLO', 'direction' => 'encrypt', 'settings' => ['key' => self::EN_KEY],
        ]);
        $lower = $this->tool->execute([
            'text' => 'HELLO', 'direction' => 'encrypt', 'settings' => ['key' => strtolower(self::EN_KEY)],
        ]);

        self::assertSame($upper['result'], $lower['result']);
    }

    /**
     * Проверяет, что пробелы вокруг ключа обрезаются.
     */
    public function testKeyIsTrimmed(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => '  ' . self::EN_KEY . '  '],
        ]);

        self::assertSame('ITSSG', $result['result']);
    }

    /**
     * Проверяет, что знаки препинания и пробелы в тексте сохраняются в результате.
     */
    public function testPunctuationAndSpacesPreservedInResult(): void
    {
        $result = $this->tool->execute([
            'text'      => 'Hello, World!',
            'direction' => 'encrypt',
            'settings'  => ['key' => self::EN_KEY],
        ]);

        self::assertStringContainsString(',', $result['result']);
        self::assertStringContainsString('!', $result['result']);
        self::assertStringContainsString(' ', $result['result']);
    }

    /**
     * Проверяет, что смешанный текст (латиница + кириллица + цифры) при английском ключе
     * шифрует только латинские буквы, остальное сохраняет.
     */
    public function testMixedTextEnglishKeyOnlyEncryptsLatinChars(): void
    {
        $result = $this->tool->execute([
            'text'      => 'A1Б',
            'direction' => 'encrypt',
            'settings'  => ['key' => self::EN_KEY],
        ]);

        // 'A'→'Q', '1'→'1', 'Б'→'Б'
        self::assertSame('Q1Б', $result['result']);
    }

    // -----------------------------------------------------------------------
    // Валидация: ошибки входных данных
    // -----------------------------------------------------------------------

    /**
     * Проверяет, что пустой текст вызывает ValidationFailedException.
     */
    public function testThrowsWhenTextIsEmpty(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => '', 'direction' => 'encrypt', 'settings' => ['key' => self::EN_KEY],
        ]);
    }

    /**
     * Проверяет, что пустой ключ вызывает ValidationFailedException.
     */
    public function testThrowsWhenKeyIsEmpty(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO', 'direction' => 'encrypt', 'settings' => ['key' => ''],
        ]);
    }

    /**
     * Проверяет, что отсутствующий ключ вызывает ValidationFailedException.
     */
    public function testThrowsWhenKeyIsMissing(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO', 'direction' => 'encrypt', 'settings' => [],
        ]);
    }

    /**
     * Проверяет, что недопустимое направление вызывает ValidationFailedException.
     */
    public function testThrowsWhenDirectionIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO', 'direction' => 'ENCRYPT', 'settings' => ['key' => self::EN_KEY],
        ]);
    }

    /**
     * Проверяет, что ключ с повторяющимися буквами (не перестановка) вызывает исключение.
     * AACDEFGHIJKLMNOPQRSTUVWXYZ — 'a' дважды, 'b' отсутствует.
     */
    public function testThrowsWhenKeyHasRepeatedLetters(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO', 'direction' => 'encrypt',
            'settings' => ['key' => 'AACDEFGHIJKLMNOPQRSTUVWXYZ'],
        ]);
    }

    /**
     * Проверяет, что слишком короткий ключ вызывает ValidationFailedException.
     */
    public function testThrowsWhenKeyIsTooShort(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO', 'direction' => 'encrypt', 'settings' => ['key' => 'ABCDEF'],
        ]);
    }

    /**
     * Проверяет, что ключ с цифрами вместо букв вызывает ValidationFailedException.
     */
    public function testThrowsWhenKeyContainsDigitsInsteadOfLetters(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO', 'direction' => 'encrypt',
            'settings' => ['key' => '12345678901234567890123456'],
        ]);
    }

    /**
     * Проверяет ошибку несовпадения алфавита: текст из цифр и знаков с английским ключом.
     */
    public function testThrowsWhenTextHasNoAlphabetCharsForEnglishKey(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => '12345 !@#', 'direction' => 'encrypt', 'settings' => ['key' => self::EN_KEY],
        ]);
    }

    /**
     * Проверяет ошибку несовпадения алфавита: русский текст при английском ключе.
     */
    public function testThrowsWhenRussianTextUsedWithEnglishKey(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'Привет мир', 'direction' => 'encrypt', 'settings' => ['key' => self::EN_KEY],
        ]);
    }

    /**
     * Проверяет ошибку несовпадения алфавита: латинский текст при русском ключе.
     */
    public function testThrowsWhenEnglishTextUsedWithRussianKey(): void
    {
        $this->expectException(ValidationFailedException::class);

        $key = 'бвгдеёжзийклмнопрстуфхцчшщъыьэюяа';

        $this->tool->execute([
            'text' => 'Hello', 'direction' => 'encrypt', 'settings' => ['key' => $key],
        ]);
    }

    /**
     * Проверяет, что settings — не массив — обрабатывается без fatal error (ключ пустой → ошибка).
     */
    public function testThrowsWhenSettingsIsNotArray(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO', 'direction' => 'encrypt', 'settings' => 'not-an-array',
        ]);
    }
}
