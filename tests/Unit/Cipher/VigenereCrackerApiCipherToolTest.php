<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cache\NullCache;
use App\Cipher\AlphabetCatalog;
use App\Cipher\BigramFrequencyScorer;
use App\Cipher\LetterFrequencyScorer;
use App\Cipher\VigenereCipherService;
use App\Cipher\VigenereCrackerApiCipherTool;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента автоматического взлома шифра Виженера.
 */
final class VigenereCrackerApiCipherToolTest extends TestCase
{
    private VigenereCipherService $vigenere;
    private VigenereCrackerApiCipherTool $tool;

    protected function setUp(): void
    {
        $catalog        = new AlphabetCatalog();
        $this->vigenere = new VigenereCipherService($catalog);
        $this->tool     = new VigenereCrackerApiCipherTool(
            $this->vigenere,
            new LetterFrequencyScorer(),
            $catalog,
            new BigramFrequencyScorer(),
            new NullCache()
        );
    }

    /**
     * Проверяет, что action() возвращает 'vigenere-cracker'.
     */
    public function testActionReturnsVigenereCracker(): void
    {
        self::assertSame('vigenere-cracker', $this->tool->action());
    }

    /**
     * Восстанавливает точный русский ключ «СЕКРЕТ» из короткого текста.
     *
     * Регрессионный кейс: до перехода на биграммный скоринг алгоритм выбирал
     * длину 9 (вместо 6) и ключ «ЪЬЬЪЙРСЕР».
     */
    public function testCracksRussianTextWithExactKey(): void
    {
        $plain     = 'Шифруйте и расшифровывайте текст шифром Виженера онлайн, используя собственное ключевое слово и выбранный алфавит.';
        $encrypted = $this->vigenere->process($plain, 'Секрет', 'ru', 'encrypt');

        $result = $this->tool->execute(['text' => $encrypted, 'settings' => ['alphabet' => 'ru']]);

        self::assertTrue($result['ok']);
        self::assertSame('СЕКРЕТ', $result['key']);
        self::assertSame(6, $result['key_length']);
        self::assertSame($plain, $result['decrypted']);
    }

    /**
     * Восстанавливает английский ключ «SECRET» в коротком тексте.
     */
    public function testCracksEnglishTextWithSecretKey(): void
    {
        $plain     = 'Encrypt and decrypt text with Vigenere cipher online using your own custom keyword and selected alphabet.';
        $encrypted = $this->vigenere->process($plain, 'SECRET', 'en', 'encrypt');

        $result = $this->tool->execute(['text' => $encrypted, 'settings' => ['alphabet' => 'en']]);

        self::assertSame('SECRET', $result['key']);
        self::assertSame(6, $result['key_length']);
        self::assertSame($plain, $result['decrypted']);
    }

    /**
     * Восстанавливает английский ключ «HAMLET» в литературной цитате.
     */
    public function testCracksEnglishTextWithHamletKey(): void
    {
        $plain     = 'To be, or not to be, that is the question: Whether tis nobler in the mind to suffer the slings and arrows.';
        $encrypted = $this->vigenere->process($plain, 'HAMLET', 'en', 'encrypt');

        $result = $this->tool->execute(['text' => $encrypted, 'settings' => ['alphabet' => 'en']]);

        self::assertSame('HAMLET', $result['key']);
        self::assertSame($plain, $result['decrypted']);
    }

    /**
     * Восстанавливает длинный русский ключ «КРИПТОГРАФИЯ» в более длинном тексте.
     */
    public function testCracksLongRussianText(): void
    {
        $plain     = 'Шифр Виженера — это полиалфавитный шифр, разработанный Блезом де Виженером в шестнадцатом веке. Каждый символ открытого текста сдвигается на величину, определяемую очередной буквой ключевого слова. Этот шифр считался невзламываемым на протяжении трёх столетий, пока Чарльз Бэббидж не нашёл способ его взлома.';
        $encrypted = $this->vigenere->process($plain, 'Криптография', 'ru', 'encrypt');

        $result = $this->tool->execute(['text' => $encrypted, 'settings' => ['alphabet' => 'ru']]);

        self::assertSame('КРИПТОГРАФИЯ', $result['key']);
        self::assertSame($plain, $result['decrypted']);
    }

    /**
     * Автоопределяет язык шифртекста при alphabet='auto'.
     */
    public function testAutoDetectsRussianAlphabet(): void
    {
        $plain     = 'Шифруйте и расшифровывайте текст шифром Виженера онлайн, используя собственное ключевое слово и выбранный алфавит.';
        $encrypted = $this->vigenere->process($plain, 'Секрет', 'ru', 'encrypt');

        $result = $this->tool->execute(['text' => $encrypted, 'settings' => ['alphabet' => 'auto']]);

        self::assertSame('ru', $result['alphabet']);
        self::assertSame('ru', $result['detected_alphabet']);
        self::assertSame('СЕКРЕТ', $result['key']);
    }

    /**
     * Возвращает короткие тексты как ненадёжный результат, не падая.
     */
    public function testShortTextReturnsUnreliable(): void
    {
        $result = $this->tool->execute(['text' => 'абв', 'settings' => ['alphabet' => 'ru']]);

        self::assertTrue($result['ok']);
        self::assertFalse($result['reliable']);
    }

    /**
     * Текст длиннее 5000 символов отклоняется на этапе валидации, не доходя до
     * тяжёлого расчёта. Защита от DoS: алгоритм имеет сложность,
     * пропорциональную длине текста.
     */
    public function testRejectsTextLongerThanLimit(): void
    {
        $limit   = VigenereCrackerApiCipherTool::MAX_TEXT_LENGTH;
        $tooLong = str_repeat('а', $limit + 1);

        try {
            $this->tool->execute(['text' => $tooLong, 'settings' => ['alphabet' => 'ru']]);
            self::fail('Expected ValidationFailedException was not thrown.');
        } catch (ValidationFailedException $e) {
            $message = (string) ($e->details()['errors']['text'][0] ?? '');
            self::assertStringContainsString((string) $limit, $message, 'Лимит должен быть подставлен в сообщение.');
            self::assertStringNotContainsString(':limit', $message, 'Плейсхолдер должен быть заменён.');
        }
    }
}
