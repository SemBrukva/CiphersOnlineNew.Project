<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента визуализации частот букв.
 */
final readonly class LetterFrequencyService
{
    /**
     * Возвращает UI-настройки инструмента: язык и сортировка.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-lfreq-lang',
                'label'   => trans('LFREQ_SETTING_LANG_LABEL'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'auto', 'label' => trans('LFREQ_SETTING_LANG_AUTO'), 'selected' => true],
                    ['value' => 'en', 'label' => trans('LANG_NAME_EN')],
                    ['value' => 'ru', 'label' => trans('LANG_NAME_RU')],
                    ['value' => 'de', 'label' => trans('LANG_NAME_DE')],
                    ['value' => 'es', 'label' => trans('LANG_NAME_ES')],
                    ['value' => 'fr', 'label' => trans('LANG_NAME_FR')],
                    ['value' => 'it', 'label' => trans('LANG_NAME_IT')],
                    ['value' => 'pt', 'label' => trans('LANG_NAME_PT')],
                    ['value' => 'tr', 'label' => trans('LANG_NAME_TR')],
                ],
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-lfreq-sort',
                'label'   => trans('LFREQ_SETTING_SORT_LABEL'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'alpha',     'label' => trans('LFREQ_SETTING_SORT_ALPHA'),     'selected' => true],
                    ['value' => 'frequency', 'label' => trans('LFREQ_SETTING_SORT_FREQ')],
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
            trans('LFREQ_TRUST_CLIENT'),
            trans('LFREQ_TRUST_UNICODE'),
            trans('LFREQ_TRUST_INSTANT'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
