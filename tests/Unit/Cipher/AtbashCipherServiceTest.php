<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AtbashCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Атбаш.
 */
final class AtbashCipherServiceTest extends TestCase
{
    /**
     * Проверяет симметричность преобразования Атбаш.
     */
    public function testProcessIsReciprocal(): void
    {
        $service = new AtbashCipherService();

        $encrypted = $service->process('HELLO WORLD', 'en');
        self::assertSame('SVOOL DLIOW', $encrypted);

        $decrypted = $service->process($encrypted, 'en');
        self::assertSame('HELLO WORLD', $decrypted);
    }
}
