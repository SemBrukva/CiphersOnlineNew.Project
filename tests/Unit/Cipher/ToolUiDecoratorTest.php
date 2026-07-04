<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\CipherIdentifierApiCipherTool;
use App\Cipher\ToolUiDecorator;
use PHPUnit\Framework\TestCase;

/**
 * Тесты декоратора tool_ui, дополняющего конфигурацию виджета под конкретный инструмент.
 */
final class ToolUiDecoratorTest extends TestCase
{
    /**
     * Проверяет, что для обычного инструмента без спец-режима tool_ui не мутирует.
     */
    public function testPlainToolIsUnchanged(): void
    {
        $decorator = new ToolUiDecorator();
        $result = $decorator->decorate(['foo' => 'bar'], 'classical-ciphers', 'atbash');

        self::assertSame(['foo' => 'bar'], $result);
    }

    /**
     * Проверяет установку флага режима и лейбла для визуального инструмента (Pigpen).
     */
    public function testPigpenModeFlagIsSet(): void
    {
        $decorator = new ToolUiDecorator();
        $result = $decorator->decorate([], 'codes-and-alphabets', 'pigpen');

        self::assertTrue($result['pigpenMode']);
        self::assertArrayHasKey('pigpenKeyboardHint', $result);
    }

    /**
     * Проверяет режим text-diff.
     */
    public function testTextDiffModeFlagIsSet(): void
    {
        $decorator = new ToolUiDecorator();
        $result = $decorator->decorate([], 'text-analysis', 'text-diff');

        self::assertTrue($result['diffMode']);
    }

    /**
     * Проверяет режим cipher-identifier: флаг, лимит длины и словарь переводов.
     */
    public function testCipherIdentifierModeCarriesLimitAndTranslations(): void
    {
        $decorator = new ToolUiDecorator();
        $result = $decorator->decorate([], 'text-analysis', 'cipher-identifier');

        self::assertTrue($result['identifierMode']);
        self::assertTrue($result['disableLiveMode']);
        self::assertSame(CipherIdentifierApiCipherTool::MAX_TEXT_LENGTH, $result['inputMaxLength']);
        self::assertIsArray($result['cidTranslations']);
    }

    /**
     * Проверяет, что hashing-инструменты обрабатываются через категорию.
     */
    public function testHashingCategoryIsApplied(): void
    {
        $decorator = new ToolUiDecorator();
        $result = $decorator->decorate([], 'hashing', 'md5');

        // HashingToolUi::apply добавляет как минимум одно поле — tool_ui не должен остаться пустым.
        self::assertNotSame([], $result);
    }
}
