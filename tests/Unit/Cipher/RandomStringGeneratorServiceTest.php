<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\RandomStringGeneratorCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса настроек инструмента «Генератор случайных строк».
 */
final class RandomStringGeneratorServiceTest extends TestCase
{
    /**
     * Проверяет, что generic-настроек у инструмента нет (собственный шаблон).
     */
    public function testToolSettingsAreEmpty(): void
    {
        $service = new RandomStringGeneratorCipherService();

        self::assertSame([], $service->getToolSettings());
    }

    /**
     * Проверяет, что блок доверия содержит четыре пункта и полностью клиентский.
     */
    public function testTrustItemsListedForClientMode(): void
    {
        $service = new RandomStringGeneratorCipherService();

        $items = $service->getTrustItems('client');

        self::assertCount(4, $items);
        self::assertContainsOnly('string', $items);
    }
}
