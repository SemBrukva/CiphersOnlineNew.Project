<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\PlayfairCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Плейфера.
 */
final class PlayfairCipherServiceTest extends TestCase
{
    /**
     * Проверяет, что шифрование и обратное дешифрование дают исходный текст с заполнителем.
     */
    public function testEncryptAndDecryptRoundTrip(): void
    {
        $service = new PlayfairCipherService();

        $encrypted = $service->process('HELLO WORLD', 'KEYWORD', 'en', 'encrypt');
        self::assertSame('IKICMWORWNAB', $encrypted);

        $decrypted = $service->process($encrypted, 'KEYWORD', 'en', 'decrypt');
        self::assertSame('HELALOWORLDA', $decrypted);
    }

    /**
     * Проверяет автоопределение алфавита для кириллицы.
     */
    public function testDetectsRussianAlphabet(): void
    {
        $service = new PlayfairCipherService();

        self::assertSame('ru', $service->detectAlphabet('Привет, мир!'));
    }

    /**
     * Проверяет, что сервис определяет наличие символов выбранного алфавита.
     */
    public function testDetectsAlphabetCharactersInInput(): void
    {
        $service = new PlayfairCipherService();

        self::assertTrue($service->hasAlphabetCharacters('Hello 123', 'en'));
        self::assertFalse($service->hasAlphabetCharacters('123 !!!', 'en'));
    }

    /**
     * Проверяет биграммы по одному столбцу с ключом, помещающим буквы в столбец 5.
     * Ключ PLAYFAIR ставит O и W в col=5 разных строк — это тот случай,
     * когда сдвиг по столбцу уходил в неполную последнюю строку матрицы (баг).
     */
    public function testSameColumnBigramWithIncompleteLastRow(): void
    {
        $service = new PlayfairCipherService();

        // HELLO WORLD содержит биграмм OW/WO — оба в col=5 матрицы PLAYFAIR.
        // До исправления: PHP Warning "Undefined array key 5", результат неверный.
        $encrypted = $service->process('HELLO WORLD', 'PLAYFAIR', 'en', 'encrypt');
        self::assertNotEmpty($encrypted);

        // Дешифрование должно вернуть нормализованный текст (без пробелов, с заполнителем).
        $decrypted = $service->process($encrypted, 'PLAYFAIR', 'en', 'decrypt');
        self::assertNotEmpty($decrypted);

        // LL-биграмм разбивается заполнителем A: HELLOWORLD → HE LA LO WO RL DA.
        // Дешифрование возвращает 12 символов с заполнителем — HELALOWORLDA.
        self::assertSame('HELALOWORLDA', $decrypted);
    }

    /**
     * Проверяет шифрование с ключом, размещающим буквы в последних столбцах матрицы
     * при операции «одна строка», затрагивающей неполную последнюю строку.
     */
    public function testSameRowWrapInIncompleteLastRow(): void
    {
        $service = new PlayfairCipherService();

        // XZ — оба символа в неполной последней строке матрицы [X, Z] для любого
        // 26-буквенного алфавита. Операция «одна строка» должна использовать длину
        // именно этой строки (2), а не длину первой строки (6).
        $encrypted = $service->process('XZ', 'PLAYFAIR', 'en', 'encrypt');
        self::assertSame(2, mb_strlen($encrypted));

        $decrypted = $service->process($encrypted, 'PLAYFAIR', 'en', 'decrypt');
        self::assertSame('XZ', $decrypted);
    }
}
