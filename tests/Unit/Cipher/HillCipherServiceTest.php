<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\HillCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Хилла.
 */
final class HillCipherServiceTest extends TestCase
{
    /**
     * Проверяет классический пример шифрования 2x2.
     */
    public function testEncryptsClassicTwoByTwoExample(): void
    {
        $service = new HillCipherService();
        $matrix = $service->parseMatrix('3 3; 2 5');

        self::assertSame('HIAT', $service->process('HELP', 'en', $matrix, 'encrypt'));
    }

    /**
     * Проверяет расшифрование классического примера 2x2.
     */
    public function testDecryptsClassicTwoByTwoExample(): void
    {
        $service = new HillCipherService();
        $matrix = $service->parseMatrix('3 3; 2 5');

        self::assertSame('HELP', $service->process('HIAT', 'en', $matrix, 'decrypt'));
    }

    /**
     * Проверяет сохранение регистра и пунктуации.
     */
    public function testPreservesCaseAndPunctuation(): void
    {
        $service = new HillCipherService();
        $matrix = $service->parseMatrix('3 3; 2 5');

        self::assertSame('Hi,at!', $service->process('He,lp!', 'en', $matrix, 'encrypt'));
    }

    /**
     * Проверяет добавление padding при неполном последнем блоке.
     */
    public function testPadsIncompleteEncryptionBlock(): void
    {
        $service = new HillCipherService();
        $matrix = $service->parseMatrix('3 3; 2 5');

        self::assertSame('MZ', $service->process('H', 'en', $matrix, 'encrypt'));
    }

    /**
     * Проверяет поддержку русской азбуки.
     */
    public function testProcessesRussianAlphabet(): void
    {
        $service = new HillCipherService();
        $matrix = $service->parseMatrix('1 2; 3 5');
        $encrypted = $service->process('ПРИВЕТ', 'ru', $matrix, 'encrypt');

        self::assertNotSame('ПРИВЕТ', $encrypted);
        self::assertSame('ПРИВЕТ', $service->process($encrypted, 'ru', $matrix, 'decrypt'));
    }

    /**
     * Проверяет разбор плоского списка чисел как квадратной матрицы.
     */
    public function testParsesFlatMatrix(): void
    {
        $service = new HillCipherService();

        self::assertSame([[3, 3], [2, 5]], $service->parseMatrix('3, 3, 2, 5'));
    }

    /**
     * Проверяет валидацию формы матрицы.
     */
    public function testValidatesMatrixShape(): void
    {
        $service = new HillCipherService();

        self::assertTrue($service->isSupportedMatrix([[3, 3], [2, 5]]));
        self::assertFalse($service->isSupportedMatrix([[1, 2, 3], [4, 5, 6]]));
    }

    /**
     * Проверяет определение обратимости матрицы.
     */
    public function testValidatesInvertibleMatrix(): void
    {
        $service = new HillCipherService();

        self::assertTrue($service->isInvertibleMatrix([[3, 3], [2, 5]], 26));
        self::assertFalse($service->isInvertibleMatrix([[2, 4], [2, 4]], 26));
    }
}
