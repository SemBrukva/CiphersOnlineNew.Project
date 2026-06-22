<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AlbertiCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Альберти.
 */
final class AlbertiCipherServiceTest extends TestCase
{
    private AlbertiCipherService $service;

    protected function setUp(): void
    {
        $this->service = new AlbertiCipherService();
    }

    /**
     * Проверяет генерацию внутреннего алфавита из ключевого слова.
     */
    public function testGenerateInnerAlphabetFromKeyword(): void
    {
        $inner = $this->service->generateInnerAlphabet('ALBERTI');
        self::assertCount(26, $inner);
        self::assertSame('a', $inner[0]);
        self::assertSame('l', $inner[1]);
        self::assertSame('b', $inner[2]);
        self::assertSame('e', $inner[3]);
        self::assertSame('r', $inner[4]);
        self::assertSame('t', $inner[5]);
        self::assertSame('i', $inner[6]);
        // Все 26 уникальных букв a-z
        sort($inner);
        self::assertSame(range('a', 'z'), $inner);
    }

    /**
     * Проверяет, что пустой ключ даёт стандартный алфавит A-Z.
     */
    public function testEmptyKeywordGivesStandardAlphabet(): void
    {
        $inner = $this->service->generateInnerAlphabet('');
        self::assertSame(range('a', 'z'), $inner);
    }

    /**
     * Проверяет шифрование HELLO WORLD ключом ALBERTI и индексом A.
     */
    public function testEncryptHelloWorldWithAlberti(): void
    {
        $result = $this->service->process('HELLO WORLD', 'ALBERTI', 'A', 'encrypt');
        self::assertSame('CRHHM WMPHE', $result);
    }

    /**
     * Проверяет дешифрование результата предыдущего теста.
     */
    public function testDecryptRestoresOriginalText(): void
    {
        $ciphertext = $this->service->process('HELLO WORLD', 'ALBERTI', 'A', 'encrypt');
        $plaintext  = $this->service->process($ciphertext, 'ALBERTI', 'A', 'decrypt');
        self::assertSame('HELLO WORLD', $plaintext);
    }

    /**
     * Проверяет шифрование с ключом ZEBRAS.
     */
    public function testEncryptWithZebrasKey(): void
    {
        $result = $this->service->process('ATTACK AT DAWN', 'ZEBRAS', 'A', 'encrypt');
        self::assertSame('ZQQZBH ZQ RZVK', $result);
    }

    /**
     * Проверяет дешифрование с ключом ZEBRAS.
     */
    public function testDecryptWithZebrasKey(): void
    {
        $result = $this->service->process('ZQQZBH ZQ RZVK', 'ZEBRAS', 'A', 'decrypt');
        self::assertSame('ATTACK AT DAWN', $result);
    }

    /**
     * Проверяет, что смена индекса меняет результат шифрования.
     */
    public function testDifferentIndexProducesDifferentResult(): void
    {
        $enc1 = $this->service->process('HELLO', 'SECRET', 'A', 'encrypt');
        $enc2 = $this->service->process('HELLO', 'SECRET', 'B', 'encrypt');
        self::assertNotSame($enc1, $enc2);
    }

    /**
     * Проверяет, что с индексом B дешифрование корректно.
     */
    public function testEncryptDecryptWithNonZeroIndex(): void
    {
        $encrypted = $this->service->process('TEST MESSAGE', 'KEYWORD', 'C', 'encrypt');
        $decrypted = $this->service->process($encrypted, 'KEYWORD', 'C', 'decrypt');
        self::assertSame('TEST MESSAGE', $decrypted);
    }

    /**
     * Проверяет сохранение регистра букв.
     */
    public function testPreservesCase(): void
    {
        $result = $this->service->process('Hello World', 'ALBERTI', 'A', 'encrypt');
        // строчные h→c, прописные → CRHHM
        self::assertSame('c', strtolower(substr($result, 0, 1)));
        // пробел сохраняется
        self::assertSame(' ', $result[5]);
    }

    /**
     * Проверяет, что нелатинские символы сохраняются без изменений.
     */
    public function testPreservesNonLatinCharacters(): void
    {
        $result = $this->service->process('Hi! 123', 'KEY', 'A', 'encrypt');
        self::assertStringContainsString('!', $result);
        self::assertStringContainsString(' ', $result);
        self::assertStringContainsString('1', $result);
    }

    /**
     * Проверяет вычисление смещения по букве индекса.
     */
    public function testComputeOffset(): void
    {
        self::assertSame(0, $this->service->computeOffset('A'));
        self::assertSame(1, $this->service->computeOffset('B'));
        self::assertSame(25, $this->service->computeOffset('Z'));
        self::assertSame(0, $this->service->computeOffset('a'));
    }

    /**
     * Проверяет строковое представление внутреннего алфавита.
     */
    public function testInnerAlphabetString(): void
    {
        $str = $this->service->innerAlphabetString('ZEBRAS');
        self::assertSame(26, strlen($str));
        self::assertMatchesRegularExpression('/^[A-Z]{26}$/', $str);
        self::assertStringStartsWith('Z', $str);
    }

    /**
     * Проверяет, что hasLatinCharacters() корректно работает.
     */
    public function testHasLatinCharacters(): void
    {
        self::assertTrue($this->service->hasLatinCharacters('Hello'));
        self::assertTrue($this->service->hasLatinCharacters('123 abc'));
        self::assertFalse($this->service->hasLatinCharacters('123'));
        self::assertFalse($this->service->hasLatinCharacters('Привет'));
        self::assertFalse($this->service->hasLatinCharacters(''));
    }

    /**
     * Проверяет, что нелатинские символы в ключе игнорируются.
     */
    public function testKeywordWithNonLatinCharsIgnoresThem(): void
    {
        $withNoise  = $this->service->generateInnerAlphabet('KEY123 !@#');
        $cleanOnly  = $this->service->generateInnerAlphabet('KEY');
        self::assertSame($cleanOnly, $withNoise);
    }

    /**
     * Проверяет, что повторяющиеся буквы ключа дают тот же результат, что уникальные.
     */
    public function testKeywordWithRepeatedLettersUsesUniqueOnly(): void
    {
        $repeated = $this->service->generateInnerAlphabet('AABBCC');
        $unique   = $this->service->generateInnerAlphabet('ABC');
        self::assertSame($unique, $repeated);
    }

    /**
     * Проверяет, что ключ в смешанном регистре даёт тот же алфавит, что в верхнем.
     */
    public function testMixedCaseKeywordEqualsUppercase(): void
    {
        $mixed = $this->service->generateInnerAlphabet('AlBeRtI');
        $upper = $this->service->generateInnerAlphabet('ALBERTI');
        self::assertSame($upper, $mixed);
    }

    /**
     * Проверяет шифрование/дешифрование с максимальным индексом Z (смещение 25).
     */
    public function testEncryptDecryptWithIndexZ(): void
    {
        $encrypted = $this->service->process('HELLO WORLD', 'SECRET', 'Z', 'encrypt');
        $decrypted = $this->service->process($encrypted, 'SECRET', 'Z', 'decrypt');
        self::assertSame('HELLO WORLD', $decrypted);
    }

    /**
     * Проверяет, что все 26 букв алфавита корректно шифруются и дешифруются (round-trip).
     */
    public function testFullAlphabetRoundTrip(): void
    {
        $alphabet  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $encrypted = $this->service->process($alphabet, 'KEYWORD', 'C', 'encrypt');
        $decrypted = $this->service->process($encrypted, 'KEYWORD', 'C', 'decrypt');
        self::assertSame($alphabet, $decrypted);
    }

    /**
     * Проверяет, что результат шифрования является перестановкой алфавита.
     */
    public function testEncryptedAlphabetIsPermutation(): void
    {
        $alphabet  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $encrypted = $this->service->process($alphabet, 'ALBERTI', 'A', 'encrypt');
        $sorted    = str_split($encrypted);
        sort($sorted);
        self::assertSame(str_split($alphabet), $sorted);
    }

    /**
     * Проверяет computeOffset с нелатинским вводом: должен возвращать 0.
     */
    public function testComputeOffsetWithNonLetterReturnsZero(): void
    {
        self::assertSame(0, $this->service->computeOffset(''));
        self::assertSame(0, $this->service->computeOffset('!'));
        self::assertSame(0, $this->service->computeOffset('1'));
    }

    /**
     * Проверяет шифрование/дешифрование одиночного символа.
     */
    public function testSingleCharEncryptDecrypt(): void
    {
        $enc = $this->service->process('A', 'ALBERTI', 'A', 'encrypt');
        self::assertSame(1, strlen($enc));
        self::assertSame('A', $this->service->process($enc, 'ALBERTI', 'A', 'decrypt'));
    }

    /**
     * Проверяет, что индекс Z (смещение 25) применяется корректно к computeOffset.
     */
    public function testComputeOffsetForZ(): void
    {
        self::assertSame(25, $this->service->computeOffset('Z'));
        self::assertSame(25, $this->service->computeOffset('z'));
    }
}
