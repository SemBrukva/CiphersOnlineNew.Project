<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\PolybiusSquareCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра квадрата Полибия.
 */
final class PolybiusSquareCipherServiceTest extends TestCase
{
    /**
     * Проверяет классическое английское шифрование с объединением I/J.
     */
    public function testEncryptsEnglishTextWithClassicSquare(): void
    {
        $service = new PolybiusSquareCipherService();

        self::assertSame(
            '23 15 31 31 34 52 34 42 31 14',
            $service->process('HELLO WORLD', 'en', 'encrypt', 'space')
        );
    }

    /**
     * Проверяет расшифровку английских координат.
     */
    public function testDecryptsEnglishCoordinates(): void
    {
        $service = new PolybiusSquareCipherService();

        self::assertSame(
            'helloworld',
            $service->process('23 15 31 31 34 52 34 42 31 14', 'en', 'decrypt', 'space')
        );
    }

    /**
     * Проверяет round-trip с разделителем dash и сохранением пробелов между словами.
     */
    public function testRoundTripWithDashDelimiterPreservesWordSpaces(): void
    {
        $service = new PolybiusSquareCipherService();

        $encrypted = $service->process('hello world', 'en', 'encrypt', 'dash');

        self::assertSame('23-15-31-31-34 52-34-42-31-14', $encrypted);
        self::assertSame('hello world', $service->process($encrypted, 'en', 'decrypt', 'dash'));
    }

    /**
     * Проверяет, что J шифруется как I в классическом английском квадрате.
     */
    public function testEnglishJUsesIPosition(): void
    {
        $service = new PolybiusSquareCipherService();

        self::assertSame('24 24', $service->process('ij', 'en', 'encrypt', 'space'));
    }

    /**
     * Проверяет шифрование кириллицы по прямоугольной таблице каталога.
     */
    public function testEncryptsRussianAlphabet(): void
    {
        $service = new PolybiusSquareCipherService();

        self::assertSame('42-43-25-13-21-45', $service->process('привет', 'ru', 'encrypt', 'dash'));
    }

    /**
     * Проверяет, что неизвестные координаты при расшифровке сохраняются.
     */
    public function testInvalidCoordinatesArePreservedOnDecrypt(): void
    {
        $service = new PolybiusSquareCipherService();

        self::assertSame('99', $service->process('99', 'en', 'decrypt', 'space'));
    }
}
