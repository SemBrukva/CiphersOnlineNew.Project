<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента «Генератор паролей».
 *
 * Генерация выполняется целиком на клиенте (calculation_mode='client') собственным
 * виджетом (pages/password-generator.js), поэтому стандартные настройки не
 * используются — возвращается пустой набор.
 */
final readonly class PasswordGeneratorCipherService
{
    /**
     * Возвращает UI-настройки инструмента. Все элементы управления (режим, длина,
     * наборы символов, число слов) рендерит собственный шаблон `_password_widget.tpl`,
     * поэтому набор пуст.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [];
    }

    /**
     * Возвращает элементы блока доверия.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('PASSWORD_TRUST_PURPOSE'),
            trans('PASSWORD_TRUST_SECURE'),
            trans('PASSWORD_TRUST_STRENGTH'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
