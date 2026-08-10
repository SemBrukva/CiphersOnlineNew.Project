<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AlphabetCatalog;
use App\Cipher\AlphabetTool;
use App\Cipher\CaseFolder;
use App\Cipher\NihilistCipherService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра нигилистов.
 */
final class NihilistCipherServiceTest extends TestCase
{
    /**
     * Создаёт экземпляр сервиса с реальными зависимостями.
     */
    private function createService(): NihilistCipherService
    {
        $catalog    = new AlphabetCatalog();
        $caseFolder = new CaseFolder();

        return new NihilistCipherService(
            $catalog,
            new AlphabetTool($catalog, $caseFolder),
            $caseFolder
        );
    }

    /**
     * Проверяет канонический пример из литературы (ZEBRAS / RUSSIAN).
     */
    public function testEncryptCanonicalExample(): void
    {
        $service = $this->createService();

        self::assertSame(
            '37 106 62 36 67 47 86 26',
            $service->process('DYNAMITE', 'ZEBRAS', 'RUSSIAN', 'en', 'encrypt')
        );
    }

    /**
     * Проверяет расшифровку канонического примера.
     */
    public function testDecryptCanonicalExample(): void
    {
        $service = $this->createService();

        self::assertSame(
            'DYNAMITE',
            $service->process('37 106 62 36 67 47 86 26', 'ZEBRAS', 'RUSSIAN', 'en', 'decrypt')
        );
    }

    /**
     * Проверяет, что шифротекст состоит только из чисел и пробелов.
     */
    public function testCiphertextContainsOnlyNumbers(): void
    {
        $service = $this->createService();

        $cipher = $service->process('The quick brown fox', 'ZEBRAS', 'RUSSIAN', 'en', 'encrypt');

        self::assertSame('', preg_replace('/[0-9 ]/', '', $cipher));
        self::assertNotSame('', $cipher);
    }

    /**
     * Проверяет round-trip для разных алфавитов.
     *
     * @param non-empty-string $plain
     */
    #[DataProvider('roundTripProvider')]
    public function testRoundTrip(string $plain, string $squareKey, string $additiveKey, string $alphabet, string $expected): void
    {
        $service = $this->createService();

        $cipher = $service->process($plain, $squareKey, $additiveKey, $alphabet, 'encrypt');
        $back   = $service->process($cipher, $squareKey, $additiveKey, $alphabet, 'decrypt');

        self::assertSame($expected, $back);
    }

    /**
     * Данные для round-trip: [текст, ключ квадрата, аддитивный ключ, алфавит, ожидаемый результат].
     *
     * @return array<string, array{string, string, string, string, string}>
     */
    public static function roundTripProvider(): array
    {
        return [
            'en' => ['ATTACK AT DAWN', 'ZEBRAS', 'RUSSIAN', 'en', 'ATTACKATDAWN'],
            'ru' => ['ПРИВЕТ МИР', 'КЛЮЧ', 'МОСКВА', 'ru', 'ПРИВЕТМИР'],
            'pt' => ['OLA MUNDO', 'CHAVE', 'PORTA', 'pt', 'OLAMUNDO'],
            'fr' => ['BONJOUR', 'CLEF', 'PARIS', 'fr', 'BONJOUR'],
        ];
    }

    /**
     * Проверяет объединение I и J в квадрате 5×5 (латиница).
     */
    public function testIandJShareCell(): void
    {
        $service = $this->createService();

        // J кодируется как I, поэтому расшифровка «JET» даёт «IET».
        $cipher = $service->process('JET', 'ZEBRAS', 'RUSSIAN', 'en', 'encrypt');
        self::assertSame('IET', $service->process($cipher, 'ZEBRAS', 'RUSSIAN', 'en', 'decrypt'));
    }

    /**
     * Проверяет французский алфавит (7×7) — все 8 языков поддерживаются.
     */
    public function testFrenchAlphabetIsSupported(): void
    {
        $service = $this->createService();

        self::assertTrue($service->supportsAlphabet('fr'));
        self::assertContains('fr', $service->supportedAlphabetCodes());
    }

    /**
     * Проверяет данные для визуализации: квадрат и пошаговое разложение.
     */
    public function testDetailedProvidesSquareAndSteps(): void
    {
        $service = $this->createService();

        $data = $service->processDetailed('HI', 'ZEBRAS', 'RUSSIAN', 'en', 'encrypt');

        self::assertSame(5, $data['size']);
        self::assertCount(5, $data['square']);
        self::assertSame(['Z', 'E', 'B', 'R', 'A'], $data['square'][0]);
        self::assertCount(2, $data['steps']);
        self::assertSame('H', $data['steps'][0]['symbol']);
        self::assertSame(31, $data['steps'][0]['code']);
        self::assertSame($data['steps'][0]['code'] + $data['steps'][0]['key_code'], $data['steps'][0]['cipher']);
    }

    /**
     * Проверяет распознавание числовых групп и букв квадрата.
     */
    public function testCharacterDetectionHelpers(): void
    {
        $service = $this->createService();

        self::assertTrue($service->hasNumberGroups('37 106 62'));
        self::assertFalse($service->hasNumberGroups('HELLO'));
        self::assertTrue($service->hasSquareLetters('HELLO', 'en'));
        self::assertFalse($service->hasSquareLetters('123 456', 'en'));
    }
}
