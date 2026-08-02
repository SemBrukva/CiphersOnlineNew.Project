<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AdfgvxCipherService;
use App\Cipher\AlphabetCatalog;
use App\Cipher\AlphabetTool;
use App\Cipher\CaseFolder;
use App\Cipher\ColumnarTranspositionCipherService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра ADFGVX.
 */
final class AdfgvxCipherServiceTest extends TestCase
{
    /**
     * Создаёт экземпляр сервиса с реальными зависимостями.
     */
    private function createService(): AdfgvxCipherService
    {
        $catalog    = new AlphabetCatalog();
        $caseFolder = new CaseFolder();

        return new AdfgvxCipherService(
            $catalog,
            new AlphabetTool($catalog, $caseFolder),
            $caseFolder,
            new ColumnarTranspositionCipherService()
        );
    }

    /**
     * Проверяет канонический пример шифрования с двумя ключами.
     */
    public function testEncryptClassicExample(): void
    {
        $service = $this->createService();

        self::assertSame(
            'VVVVAAAAGFFXGFDFGAGGGXGX',
            $service->process('ATTACK AT DAWN', 'PRIVACY', 'BATTLE', 'en', 'encrypt')
        );
    }

    /**
     * Проверяет расшифровку канонического примера.
     */
    public function testDecryptClassicExample(): void
    {
        $service = $this->createService();

        self::assertSame(
            'ATTACKATDAWN',
            $service->process('VVVVAAAAGFFXGFDFGAGGGXGX', 'PRIVACY', 'BATTLE', 'en', 'decrypt')
        );
    }

    /**
     * Проверяет, что шифротекст состоит только из букв A, D, F, G, V, X.
     */
    public function testCiphertextContainsOnlyLabelLetters(): void
    {
        $service = $this->createService();

        $cipher = $service->process('The quick brown fox', 'CIPHER', 'KEY', 'en', 'encrypt');

        self::assertSame('', preg_replace('/[ADFGVX]/', '', $cipher));
        self::assertNotSame('', $cipher);
    }

    /**
     * Проверяет, что цифры входят в открытый текст (особенность 6×6 ADFGVX).
     */
    public function testDigitsAreEncrypted(): void
    {
        $service = $this->createService();

        $cipher = $service->process('AGENT 007', 'SECRET', 'GERMAN', 'en', 'encrypt');

        self::assertSame('AGENT007', $service->process($cipher, 'SECRET', 'GERMAN', 'en', 'decrypt'));
    }

    /**
     * Проверяет round-trip для разных алфавитов.
     *
     * @param non-empty-string $plain
     */
    #[DataProvider('roundTripProvider')]
    public function testRoundTrip(string $plain, string $squareKey, string $transKey, string $alphabet, string $expected): void
    {
        $service = $this->createService();

        $cipher = $service->process($plain, $squareKey, $transKey, $alphabet, 'encrypt');
        $back   = $service->process($cipher, $squareKey, $transKey, $alphabet, 'decrypt');

        self::assertSame($expected, $back);
    }

    /**
     * Данные для round-trip: [текст, ключ квадрата, ключ транспозиции, алфавит, ожидаемый результат].
     *
     * @return array<string, array{string, string, string, string, string}>
     */
    public static function roundTripProvider(): array
    {
        return [
            'en'      => ['ATTACK AT DAWN', 'PRIVACY', 'BATTLE', 'en', 'ATTACKATDAWN'],
            'ru'      => ['ПРИВЕТ МИР', 'КЛЮЧ', 'ШИФР', 'ru', 'ПРИВЕТМИР'],
            'pt'      => ['OLA MUNDO', 'CHAVE', 'PORTA', 'pt', 'OLAMUNDO'],
            'digits'  => ['CODE 42', 'SECRET', 'KEY', 'en', 'CODE42'],
        ];
    }

    /**
     * Проверяет, что французский алфавит не поддерживается (не помещается в 6×6).
     */
    public function testFrenchAlphabetIsNotSupported(): void
    {
        $service = $this->createService();

        self::assertFalse($service->supportsAlphabet('fr'));
        self::assertNotContains('fr', $service->supportedAlphabetCodes());
        self::assertSame('', $service->process('BONJOUR', 'CLE', 'MOT', 'fr', 'encrypt'));
    }

    /**
     * Проверяет автоопределение алфавита с откатом на en для неподдерживаемых.
     */
    public function testDetectAlphabetFallsBackToEnglish(): void
    {
        $service = $this->createService();

        self::assertTrue($service->supportsAlphabet($service->detectAlphabet('HELLO WORLD')));
    }

    /**
     * Проверяет распознавание меток ADFGVX и символов квадрата.
     */
    public function testCharacterDetectionHelpers(): void
    {
        $service = $this->createService();

        self::assertTrue($service->hasLabels('VVVVAAAA'));
        self::assertFalse($service->hasLabels('12345'));
        self::assertTrue($service->hasSquareCharacters('HELLO', 'en'));
        self::assertTrue($service->hasSquareCharacters('007', 'en'));
        self::assertFalse($service->hasSquareCharacters('!!!', 'en'));
    }
}
