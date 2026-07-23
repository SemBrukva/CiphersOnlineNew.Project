<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\UuidGeneratorCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса настроек инструмента «Генератор UUID / GUID».
 */
final class UuidGeneratorServiceTest extends TestCase
{
    /**
     * Проверяет, что generic-настроек у инструмента нет (собственный шаблон).
     */
    public function testToolSettingsAreEmpty(): void
    {
        $service = new UuidGeneratorCipherService();

        self::assertSame([], $service->getToolSettings());
    }

    /**
     * Проверяет, что блок доверия содержит четыре пункта и полностью клиентский.
     */
    public function testTrustItemsListedForClientMode(): void
    {
        $service = new UuidGeneratorCipherService();

        $items = $service->getTrustItems('client');

        self::assertCount(4, $items);
        self::assertContainsOnly('string', $items);
    }
}
