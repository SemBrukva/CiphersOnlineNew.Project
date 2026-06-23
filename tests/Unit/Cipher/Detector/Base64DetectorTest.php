<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\Base64Detector;

/**
 * Тесты детектора Base64-кодировки.
 */
final class Base64DetectorTest extends DetectorTestCase
{
    private Base64Detector $detector;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new Base64Detector();
    }

    /**
     * Проверяет, что валидный Base64 определяется с уверенностью > 0.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('SGVsbG8gV29ybGQh'));
        self::assertNotNull($result);
        self::assertGreaterThan(0.0, $result->confidence);
    }

    /**
     * Проверяет, что неподходящий ввод возвращает null.
     */
    public function testRejectsInvalidInput(): void
    {
        $result = $this->detector->detect($this->ctx('HELLO WORLD!'));
        self::assertNull($result);
    }

    /**
     * Проверяет, что пустая строка не вызывает ошибок и возвращает null.
     */
    public function testEmptyStringReturnsNull(): void
    {
        $result = $this->detector->detect($this->ctx(''));
        self::assertNull($result);
    }

    /**
     * Проверяет, что Base64 с PNG-сигнатурой получает максимальный confidence
     * и помечает формат файла в hints.
     */
    public function testRecognizesPngFileSignature(): void
    {
        // 8 байт PNG magic + ещё пара произвольных байт.
        $bytes  = "\x89PNG\r\n\x1a\nIHDR";
        $base64 = base64_encode($bytes);

        $result = $this->detector->detect($this->ctx($base64));

        self::assertNotNull($result);
        self::assertGreaterThanOrEqual(0.95, $result->confidence);
        self::assertContains('CID_EV_FILE_SIGNATURE', $result->evidenceKeys);
        self::assertSame('PNG', $result->hints['file_format']);
    }

    /**
     * Проверяет, что Base64-кодированный ZIP/Office-документ распознаётся по PK\x03\x04.
     */
    public function testRecognizesZipFileSignature(): void
    {
        $bytes  = "\x50\x4b\x03\x04\x14\x00\x00\x00";
        $base64 = base64_encode($bytes);

        $result = $this->detector->detect($this->ctx($base64));

        self::assertNotNull($result);
        self::assertSame('ZIP/Office', $result->hints['file_format']);
    }

    /**
     * Проверяет, что Base64 с произвольными бинарными байтами без UTF-8 и без
     * file-signature возвращает null (раньше детектор ложно срабатывал).
     */
    public function testRandomBinaryWithoutUtf8AndSignatureReturnsNull(): void
    {
        $bytes  = "\xde\xad\xbe\xef\x00\x01\x02\x03\xff\xfe\xfd\xfc";
        $base64 = base64_encode($bytes);

        $result = $this->detector->detect($this->ctx($base64));

        self::assertNull($result);
    }
}
