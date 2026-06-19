<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента перебора всех ключей аффинного шифра.
 */
final readonly class AffineBruteForceService
{
    /**
     * Возвращает UI-настройки инструмента: выбор алфавита.
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
                    ['value' => 'es', 'label' => trans('LANG_ES')],
                    ['value' => 'pt', 'label' => trans('LANG_PT')],
                    ['value' => 'tr', 'label' => trans('LANG_TR')],
                    ['value' => 'fr', 'label' => trans('LANG_FR')],
                    ['value' => 'de', 'label' => trans('LANG_DE')],
                    ['value' => 'it', 'label' => trans('LANG_IT')],
                ],
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для инструмента перебора аффинного шифра.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('AFFINE_BRUTE_TRUST_TYPE'),
            trans('CIPHER_TOOL_TRUST_MULTI_ALPHA'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            trans('CIPHER_TOOL_TRUST_SERVER'),
        ];
    }
}
