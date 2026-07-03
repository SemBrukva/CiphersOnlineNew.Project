<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента «Шифр масонов» (Pigpen).
 */
final readonly class PigpenCipherService
{
    /**
     * Возвращает UI-настройки инструмента: выбор варианта шифра.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-pigpen-variant',
                'label'   => trans('PIGPEN_VARIANT_LABEL'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'standard',    'label' => trans('PIGPEN_VARIANT_STANDARD'), 'selected' => true],
                    ['value' => 'variant',     'label' => trans('PIGPEN_VARIANT_ALT')],
                    ['value' => 'rosicrucian', 'label' => trans('PIGPEN_VARIANT_ROSICRUCIAN')],
                ],
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для инструмента.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('PIGPEN_TRUST_MASONIC'),
            trans('PIGPEN_TRUST_KEYLESS'),
            trans('PIGPEN_TRUST_VISUAL'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
