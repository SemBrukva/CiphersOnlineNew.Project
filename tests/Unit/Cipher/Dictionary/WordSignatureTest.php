<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Dictionary;

use App\Cipher\AlphabetCatalog;
use App\Cipher\Dictionary\WordSignature;
use PHPUnit\Framework\TestCase;

/**
 * Тесты нормализатора буквенной сигнатуры слов.
 */
final class WordSignatureTest extends TestCase
{
    private WordSignature $signature;

    protected function setUp(): void
    {
        $this->signature = new WordSignature(new AlphabetCatalog());
    }

    /**
     * Сигнатура — отсортированные буквы слова в нижнем регистре.
     */
    public function testComputeSortsLowercaseLetters(): void
    {
        self::assertSame('eilnst', $this->signature->compute('LISTEN', 'en'));
        self::assertSame('eilnst', $this->signature->compute('Silent', 'en'));
    }

    /**
     * Не-алфавитные символы и пробелы отбрасываются.
     */
    public function testNonAlphabetCharactersAreDropped(): void
    {
        self::assertSame('aceios', $this->signature->compute('A.C.E! O-IS?', 'en'));
    }

    /**
     * Подмножество сигнатуры детектируется корректно.
     */
    public function testIsSubsetSignatureDetectsContainment(): void
    {
        self::assertTrue($this->signature->isSubsetSignature('els', 'eilnst'));
        self::assertTrue($this->signature->isSubsetSignature('', 'abc'));
        self::assertFalse($this->signature->isSubsetSignature('elsx', 'eilnst'));
        self::assertFalse($this->signature->isSubsetSignature('ee', 'eilnst'));
    }

    /**
     * subtractSignature возвращает остаток или null.
     */
    public function testSubtractSignature(): void
    {
        self::assertSame('iln', $this->signature->subtractSignature('eilnst', 'est'));
        self::assertSame('eilnst', $this->signature->subtractSignature('eilnst', ''));
        self::assertNull($this->signature->subtractSignature('eilnst', 'x'));
    }

    /**
     * Сигнатура работает для русского алфавита.
     */
    public function testHandlesCyrillicAlphabet(): void
    {
        self::assertSame('ккоот', $this->signature->compute('Кот-о-к', 'ru'));
    }
}
