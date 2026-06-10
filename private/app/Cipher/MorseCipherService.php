<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента азбуки Морзе.
 */
final readonly class MorseCipherService
{
    /**
     * Возвращает UI-настройки инструмента: выбор языка/алфавита.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-alphabet',
                'label'   => trans('CIPHER_TOOL_SETTING_ALPHABET'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'auto', 'label' => trans('CIPHER_TOOL_SETTING_AUTO'), 'selected' => true],
                    ['value' => 'en', 'label' => trans('LANG_EN')],
                    ['value' => 'ru', 'label' => trans('LANG_RU')],
                    ['value' => 'de', 'label' => trans('LANG_DE')],
                    ['value' => 'es', 'label' => trans('LANG_ES')],
                    ['value' => 'fr', 'label' => trans('LANG_FR')],
                    ['value' => 'it', 'label' => trans('LANG_IT')],
                    ['value' => 'pt', 'label' => trans('LANG_PT')],
                    ['value' => 'tr', 'label' => trans('LANG_TR')],
                ],
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для азбуки Морзе.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('MORSE_TRUST_STANDARD'),
            trans('MORSE_TRUST_KEYLESS'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
