<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента частотного анализа текста.
 */
final readonly class FrequencyAnalysisService
{
    /**
     * Возвращает UI-настройки инструмента: язык сравнения, область анализа, сортировка.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-freq-lang',
                'label'   => trans('FREQ_SETTING_LANG_LABEL'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'en', 'label' => 'English',    'selected' => true],
                    ['value' => 'ru', 'label' => 'Русский'],
                    ['value' => 'de', 'label' => 'Deutsch'],
                    ['value' => 'es', 'label' => 'Español'],
                    ['value' => 'fr', 'label' => 'Français'],
                    ['value' => 'it', 'label' => 'Italiano'],
                    ['value' => 'pt', 'label' => 'Português'],
                    ['value' => 'tr', 'label' => 'Türkçe'],
                ],
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-freq-scope',
                'label'   => trans('FREQ_SETTING_SCOPE_LABEL'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'letters',  'label' => trans('FREQ_SETTING_SCOPE_LETTERS'),  'selected' => true],
                    ['value' => 'all',      'label' => trans('FREQ_SETTING_SCOPE_ALL')],
                    ['value' => 'words',    'label' => trans('FREQ_SETTING_SCOPE_WORDS')],
                    ['value' => 'bigrams',  'label' => trans('FREQ_SETTING_SCOPE_BIGRAMS')],
                    ['value' => 'trigrams', 'label' => trans('FREQ_SETTING_SCOPE_TRIGRAMS')],
                ],
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-freq-sort',
                'label'   => trans('FREQ_SETTING_SORT_LABEL'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'frequency', 'label' => trans('FREQ_SETTING_SORT_FREQUENCY'), 'selected' => true],
                    ['value' => 'alpha',     'label' => trans('FREQ_SETTING_SORT_ALPHA')],
                ],
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для частотного анализа.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('FREQ_TRUST_CLIENT'),
            trans('FREQ_TRUST_UNICODE'),
            trans('FREQ_TRUST_INSTANT'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
