<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента Base85 / Ascii85 (кодирование/декодирование в браузере).
 */
final readonly class Base85CipherService
{
    /**
     * Возвращает UI-настройки инструмента: выбор варианта Base85.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-base-variant',
                'label'   => trans('BASE85_SETTING_VARIANT_LABEL'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'ascii85', 'label' => trans('BASE85_VARIANT_ASCII85'), 'selected' => true],
                    ['value' => 'z85',     'label' => trans('BASE85_VARIANT_Z85')],
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
            trans('BASE85_TRUST_PURPOSE'),
            trans('BASE85_TRUST_USES'),
            trans('CIPHER_TOOL_TRUST_UTF8'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
