<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\PasswordGeneratorCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса настроек инструмента «Генератор паролей».
 */
final class PasswordGeneratorServiceTest extends TestCase
{
    /**
     * Проверяет, что generic-настроек у инструмента нет (собственный шаблон).
     */
    public function testToolSettingsAreEmpty(): void
    {
        $service = new PasswordGeneratorCipherService();

        self::assertSame([], $service->getToolSettings());
    }

    /**
     * Проверяет, что блок доверия содержит четыре пункта и полностью клиентский.
     */
    public function testTrustItemsListedForClientMode(): void
    {
        $service = new PasswordGeneratorCipherService();

        $items = $service->getTrustItems('client');

        self::assertCount(4, $items);
        self::assertContainsOnly('string', $items);
    }
}
