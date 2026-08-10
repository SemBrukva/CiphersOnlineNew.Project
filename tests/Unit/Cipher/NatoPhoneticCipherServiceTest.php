<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\NatoPhoneticCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса настроек фонетического алфавита NATO.
 */
final class NatoPhoneticCipherServiceTest extends TestCase
{
    /**
     * Проверяет состав настроек: вариант алфавита, разделитель и формат вывода.
     */
    public function testToolSettingsExposeVariantSeparatorAndFormat(): void
    {
        $service = new NatoPhoneticCipherService();

        $ids = array_column($service->getToolSettings(), 'id');

        self::assertContains('ciphers-nato-variant', $ids);
        self::assertContains('ciphers-nato-separator', $ids);
        self::assertContains('ciphers-nato-show-letter', $ids);
    }

    /**
     * Проверяет, что селект варианта предлагает NATO, авиацию, полицию и немецкий.
     */
    public function testVariantSelectListsFourVariants(): void
    {
        $service = new NatoPhoneticCipherService();

        $variant = $this->settingById($service, 'ciphers-nato-variant');

        self::assertNotNull($variant);
        self::assertSame(['nato', 'aviation', 'police', 'german'], array_column($variant['options'], 'value'));
        self::assertTrue($variant['options'][0]['selected'] ?? false);
    }

    /**
     * Проверяет, что селект разделителя предлагает четыре варианта.
     */
    public function testSeparatorSelectListsFourOptions(): void
    {
        $service = new NatoPhoneticCipherService();

        $separator = $this->settingById($service, 'ciphers-nato-separator');

        self::assertNotNull($separator);
        self::assertSame(['space', 'hyphen', 'comma', 'newline'], array_column($separator['options'], 'value'));
    }

    /**
     * Проверяет, что блок доверия непустой для клиентского режима.
     */
    public function testTrustItemsAreProvided(): void
    {
        $service = new NatoPhoneticCipherService();

        self::assertNotEmpty($service->getTrustItems('client'));
    }

    /**
     * Возвращает настройку по её id либо null.
     *
     * @return array<string, mixed>|null
     */
    private function settingById(NatoPhoneticCipherService $service, string $id): ?array
    {
        foreach ($service->getToolSettings() as $setting) {
            if (($setting['id'] ?? '') === $id) {
                return $setting;
            }
        }

        return null;
    }
}
