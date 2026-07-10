<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\BookCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса настроек книжного шифра.
 */
final class BookCipherServiceTest extends TestCase
{
    /**
     * Проверяет состав настроек: селект схемы, разделитель и textarea референсного текста.
     */
    public function testToolSettingsExposeSchemeDelimiterAndReferenceText(): void
    {
        $service = new BookCipherService();

        $settings = $service->getToolSettings();
        $ids = array_column($settings, 'id');

        self::assertContains('ciphers-book-scheme', $ids);
        self::assertContains('ciphers-delimiter', $ids);
        self::assertContains('ciphers-key', $ids);
    }

    /**
     * Проверяет, что селект схемы предлагает все четыре схемы адресации.
     */
    public function testSchemeSelectListsFourSchemes(): void
    {
        $service = new BookCipherService();

        $settings = $service->getToolSettings();
        $scheme = null;
        foreach ($settings as $setting) {
            if (($setting['id'] ?? '') === 'ciphers-book-scheme') {
                $scheme = $setting;
                break;
            }
        }

        self::assertNotNull($scheme);
        $values = array_column($scheme['options'], 'value');
        self::assertSame(['word-index', 'beale', 'line-word', 'char-index'], $values);
    }

    /**
     * Проверяет, что референсный текст — многострочное поле (textarea).
     */
    public function testReferenceTextIsTextarea(): void
    {
        $service = new BookCipherService();

        $reference = null;
        foreach ($service->getToolSettings() as $setting) {
            if (($setting['id'] ?? '') === 'ciphers-key') {
                $reference = $setting;
                break;
            }
        }

        self::assertNotNull($reference);
        self::assertSame('textarea', $reference['type']);
    }

    /**
     * Проверяет, что блок доверия содержит четыре пункта и полностью клиентский.
     */
    public function testTrustItemsListedForClientMode(): void
    {
        $service = new BookCipherService();

        $items = $service->getTrustItems('client');

        self::assertCount(4, $items);
        self::assertContainsOnly('string', $items);
    }
}
