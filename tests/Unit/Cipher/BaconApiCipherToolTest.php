<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\BaconApiCipherTool;
use App\Cipher\BaconCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра Бэкона.
 */
final class BaconApiCipherToolTest extends TestCase
{
    private BaconApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new BaconApiCipherTool(new BaconCipherService());
    }

    // ── структура инструмента ──────────────────────────────────────────────────────

    /**
     * Проверяет, что action() возвращает строку 'bacon'.
     */
    public function testActionReturnsBacon(): void
    {
        self::assertSame('bacon', $this->tool->action());
    }

    // ── стандартное шифрование (A/B) ──────────────────────────────────────────────

    /**
     * Проверяет стандартное шифрование A/B-кодом.
     *
     * a=AAAAA, b=AAAAB, c=AAABA → AAAAAAAAABAAABA
     */
    public function testStandardEncryptReturnsABCode(): void
    {
        $result = $this->tool->execute([
            'text'      => 'abc',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('AAAAAAAAABAAABA', $result['result']);
        self::assertSame('en', $result['alphabet']);
        self::assertNull($result['detected_alphabet']);
    }

    /**
     * Проверяет стандартное расшифрование A/B-кода.
     */
    public function testStandardDecryptReturnsPlainText(): void
    {
        $result = $this->tool->execute([
            'text'      => 'AAAAAAAAABAAABA',
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'en'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('abc', $result['result']);
    }

    /**
     * Проверяет round-trip стандартного шифрования.
     */
    public function testStandardEncryptDecryptRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'hello',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en'],
        ]);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'en'],
        ]);

        self::assertSame('hello', $dec['result']);
    }

    // ── стеганографическое шифрование ─────────────────────────────────────────────

    /**
     * Проверяет стеганографическое шифрование с cover-текстом.
     *
     * Стего-текст визуально выглядит как обычный текст, но содержит скрытое сообщение.
     */
    public function testStegoEncryptWithCoverTextReturnsValidStegoText(): void
    {
        $cover = 'The quick brown fox jumps over the lazy dog and some more words here';

        $result = $this->tool->execute([
            'text'      => 'hi',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'cover_text' => $cover],
        ]);

        self::assertTrue((bool) $result['ok']);
        // stego-текст содержит буквы не только A/B — значит, это не чистый A/B-код
        self::assertTrue((new BaconCipherService())->isStegoText($result['result']));
    }

    /**
     * Проверяет стеганографический round-trip.
     */
    public function testStegoEncryptDecryptRoundTrip(): void
    {
        $cover  = 'The quick brown fox jumps over the lazy dog and some more words here now';
        $secret = 'hi';

        $enc = $this->tool->execute([
            'text'      => $secret,
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'cover_text' => $cover],
        ]);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'en'],
        ]);

        self::assertSame($secret, $dec['result']);
    }

    // ── автоопределение алфавита ───────────────────────────────────────────────────

    /**
     * Проверяет, что при auto + encrypt алфавит определяется по тексту.
     */
    public function testAutoDetectsAlphabetOnEncrypt(): void
    {
        $result = $this->tool->execute([
            'text'      => 'абв',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'auto'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ru', $result['detected_alphabet']);
        self::assertSame('ru', $result['alphabet']);
    }

    /**
     * Проверяет, что при auto + decrypt алфавит всегда 'en' (для A/B-кода).
     *
     * A/B-текст состоит только из букв A и B — нельзя определить язык,
     * поэтому инструмент принудительно использует 'en'.
     */
    public function testAutoDecryptAlwaysUsesEnglishAlphabet(): void
    {
        $result = $this->tool->execute([
            'text'      => 'AAAAAAAAABAAABA',
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'auto'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('en', $result['detected_alphabet']);
        self::assertSame('en', $result['alphabet']);
    }

    // ── валидация ──────────────────────────────────────────────────────────────────

    /**
     * Проверяет, что пустой текст вызывает ValidationFailedException.
     */
    public function testThrowsWhenTextIsEmpty(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => '',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en'],
        ]);
    }

    /**
     * Проверяет, что недопустимое направление вызывает ValidationFailedException.
     */
    public function testThrowsWhenDirectionIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'abc',
            'direction' => 'bad',
            'settings'  => ['alphabet' => 'en'],
        ]);
    }

    /**
     * Проверяет, что неизвестный алфавит вызывает ValidationFailedException.
     */
    public function testThrowsWhenAlphabetIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'abc',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'xx'],
        ]);
    }

    /**
     * Проверяет, что текст без символов выбранного алфавита при шифровании вызывает ошибку.
     */
    public function testThrowsWhenEncryptTextHasNoAlphabetCharacters(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => '123 !!!',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en'],
        ]);
    }

    /**
     * Проверяет, что слишком короткий cover-текст вызывает ValidationFailedException.
     *
     * 'hi' = 10 бит тела + 10 бит заголовка = 20 букв; cover 'abc' — всего 3 буквы.
     */
    public function testThrowsWhenCoverTextIsTooShort(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'hi',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'cover_text' => 'abc'],
        ]);
    }

    /**
     * Проверяет, что при стего-ошибке поле cover_text присутствует в errors.
     */
    public function testCoverTextErrorFieldPresentInDetails(): void
    {
        try {
            $this->tool->execute([
                'text'      => 'hello world',
                'direction' => 'encrypt',
                'settings'  => ['alphabet' => 'en', 'cover_text' => 'short'],
            ]);
            self::fail('ValidationFailedException ожидался');
        } catch (ValidationFailedException $e) {
            $errors = $e->details()['errors'] ?? [];
            self::assertArrayHasKey('settings.cover_text', $errors);
        }
    }

    /**
     * Проверяет, что при множественных ошибках все поля присутствуют в details.
     */
    public function testValidationErrorsContainAllInvalidFields(): void
    {
        try {
            $this->tool->execute([
                'text'      => '',
                'direction' => 'bad',
                'settings'  => ['alphabet' => 'xx'],
            ]);
            self::fail('ValidationFailedException ожидался');
        } catch (ValidationFailedException $e) {
            $errors = $e->details()['errors'] ?? [];
            self::assertArrayHasKey('direction', $errors);
            self::assertArrayHasKey('text', $errors);
            self::assertArrayHasKey('settings.alphabet', $errors);
        }
    }
}
