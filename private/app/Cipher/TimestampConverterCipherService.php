<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента Timestamp Converter.
 */
final readonly class TimestampConverterCipherService
{
    /**
     * Возвращает UI-настройки инструмента.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-ts-unit',
                'label'   => trans('TIMESTAMP_CONVERTER_SETTING_UNIT'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'auto',    'label' => trans('TIMESTAMP_CONVERTER_UNIT_AUTO'),    'selected' => true],
                    ['value' => 'seconds', 'label' => trans('TIMESTAMP_CONVERTER_UNIT_SECONDS')],
                    ['value' => 'ms',      'label' => trans('TIMESTAMP_CONVERTER_UNIT_MS')],
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
            trans('TIMESTAMP_CONVERTER_TRUST_CONVERTS'),
            trans('TIMESTAMP_CONVERTER_TRUST_FORMATS'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
