<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента автоматического взлома шифра Виженера.
 */
final readonly class VigenereCrackerService
{
    /**
     * Возвращает UI-настройки инструмента: выбор алфавита.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        $keyLengthOptions = [
            ['value' => 'auto', 'label' => trans('CIPHER_TOOL_SETTING_AUTO'), 'selected' => true],
        ];
        for ($i = 1; $i <= 20; $i++) {
            $keyLengthOptions[] = ['value' => (string) $i, 'label' => (string) $i];
        }

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
                    ['value' => 'es', 'label' => trans('LANG_ES')],
                    ['value' => 'pt', 'label' => trans('LANG_PT')],
                    ['value' => 'tr', 'label' => trans('LANG_TR')],
                    ['value' => 'fr', 'label' => trans('LANG_FR')],
                    ['value' => 'de', 'label' => trans('LANG_DE')],
                    ['value' => 'it', 'label' => trans('LANG_IT')],
                ],
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-key-length',
                'label'   => trans('CIPHER_TOOL_SETTING_KEY_LENGTH'),
                'class'   => 'ciphers-settings-select',
                'options' => $keyLengthOptions,
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для инструмента взлома.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('VIGENERE_CRACK_TRUST_TYPE'),
            trans('CIPHER_TOOL_TRUST_MULTI_ALPHA'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            trans('CIPHER_TOOL_TRUST_SERVER'),
        ];
    }
}
