<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента Base32 (кодирование/декодирование в браузере).
 */
final readonly class Base32CipherService
{
    /**
     * Возвращает UI-настройки инструмента: выбор варианта Base32.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-base-variant',
                'label'   => trans('BASE32_SETTING_VARIANT_LABEL'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'rfc4648',   'label' => trans('BASE32_VARIANT_RFC4648'), 'selected' => true],
                    ['value' => 'base32hex', 'label' => trans('BASE32_VARIANT_HEX')],
                    ['value' => 'crockford', 'label' => trans('BASE32_VARIANT_CROCKFORD')],
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
            trans('BASE32_TRUST_PURPOSE'),
            trans('BASE32_TRUST_USES'),
            trans('CIPHER_TOOL_TRUST_UTF8'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
