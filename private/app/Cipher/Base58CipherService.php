<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента Base58 (кодирование/декодирование в браузере).
 */
final readonly class Base58CipherService
{
    /**
     * Возвращает UI-настройки инструмента: выбор варианта Base58.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-base-variant',
                'label'   => trans('BASE58_SETTING_VARIANT_LABEL'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'base58',      'label' => trans('BASE58_VARIANT_RAW'), 'selected' => true],
                    ['value' => 'base58check', 'label' => trans('BASE58_VARIANT_CHECK')],
                ],
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('BASE58_TRUST_PURPOSE'),
            trans('BASE58_TRUST_USES'),
            trans('CIPHER_TOOL_TRUST_UTF8'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
