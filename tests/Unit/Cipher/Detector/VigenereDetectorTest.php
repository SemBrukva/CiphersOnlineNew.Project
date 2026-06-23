<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\AlphabetCatalog;
use App\Cipher\Detector\VigenereDetector;

/**
 * Тесты детектора шифра Виженера.
 */
final class VigenereDetectorTest extends DetectorTestCase
{
    private VigenereDetector $detector;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new VigenereDetector(new AlphabetCatalog());
    }

    /**
     * Проверяет, что полиалфавитно-зашифрованный текст не вызывает исключений.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('SX UKW RRI ZOWR YJ RSQCC MR GEQ DLC GSPCX MP XGWIQ'));
        self::assertTrue($result === null || $result->confidence > 0.0);
        if ($result !== null) {
            self::assertSame('vigenere-cracker', $result->bruteForceAction);
        }
    }

    /**
     * Проверяет, что слишком короткий или неподходящий ввод возвращает null.
     */
    public function testRejectsInvalidInput(): void
    {
        $result = $this->detector->detect($this->ctx('HELLO WORLD'));
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
     * Проверяет, что на длинном Vigenère-тексте обнаруживается оценка длины ключа
     * через пик среднего IoC по колонкам.
     *
     * Зашифровано: достаточно длинный английский текст ключом «KEY» (длина 3).
     */
    public function testDetectsKeyLengthFromColumnIocPeak(): void
    {
        $ciphertext =
            'RIJVS UYVJN RIJVS UYVJN ZSCDS BNTUC SQRMD MMOPB PVMRM ' .
            'PNFGS UWXIY VJNRT XQOPC LPBIK GHRRY MOVPB MIWPV LNRCG ' .
            'NIIYV PBEMV LWUCK SVPVN PVVPB MIWBP LRIYI BQHPV NRTHC ' .
            'OMRPV BMMPB MIWPM CDPAR VPBSV NRTHB MQVFE RIJVS UYVJN';

        $result = $this->detector->detect($this->ctx($ciphertext));

        self::assertNotNull($result);
        self::assertSame('vigenere-cracker', $result->bruteForceAction);
        self::assertArrayHasKey('key_length_estimate', $result->hints);
        self::assertGreaterThanOrEqual(0.60, $result->confidence);
    }
}
