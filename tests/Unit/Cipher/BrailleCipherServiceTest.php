<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\BrailleCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса настроек шрифта Брайля.
 */
final class BrailleCipherServiceTest extends TestCase
{
    /**
     * Проверяет состав настроек: язык, формат вывода и обработка регистра.
     */
    public function testToolSettingsExposeLanguageFormatAndCase(): void
    {
        $service = new BrailleCipherService();

        $ids = array_column($service->getToolSettings(), 'id');

        self::assertContains('ciphers-alphabet', $ids);
        self::assertContains('ciphers-braille-format', $ids);
        self::assertContains('ciphers-braille-case', $ids);
    }

    /**
     * Проверяет, что селект формата предлагает Unicode, номера точек и Braille ASCII.
     */
    public function testFormatSelectListsThreeFormats(): void
    {
        $service = new BrailleCipherService();

        $format = $this->settingById($service, 'ciphers-braille-format');

        self::assertNotNull($format);
        self::assertSame(['unicode', 'dots', 'ascii'], array_column($format['options'], 'value'));
    }

    /**
     * Проверяет, что селект языка охватывает все 8 системных языков плюс авто.
     */
    public function testLanguageSelectCoversEightLanguages(): void
    {
        $service = new BrailleCipherService();

        $language = $this->settingById($service, 'ciphers-alphabet');

        self::assertNotNull($language);
        $values = array_column($language['options'], 'value');
        self::assertContains('auto', $values);
        foreach (['en', 'ru', 'de', 'es', 'fr', 'it', 'pt', 'tr'] as $lang) {
            self::assertContains($lang, $values);
        }
    }

    /**
     * Проверяет, что селект регистра по умолчанию сохраняет регистр (знак ⠠).
     */
    public function testCaseSelectDefaultsToKeep(): void
    {
        $service = new BrailleCipherService();

        $case = $this->settingById($service, 'ciphers-braille-case');

        self::assertNotNull($case);
        self::assertSame(['keep', 'ignore'], array_column($case['options'], 'value'));
        self::assertTrue($case['options'][0]['selected'] ?? false);
    }

    /**
     * Проверяет, что блок доверия непустой для клиентского режима.
     */
    public function testTrustItemsAreProvided(): void
    {
        $service = new BrailleCipherService();

        self::assertNotEmpty($service->getTrustItems('client'));
    }

    /**
     * Возвращает настройку по её id либо null.
     *
     * @return array<string, mixed>|null
     */
    private function settingById(BrailleCipherService $service, string $id): ?array
    {
        foreach ($service->getToolSettings() as $setting) {
            if (($setting['id'] ?? '') === $id) {
                return $setting;
            }
        }

        return null;
    }
}
