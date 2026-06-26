<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента поиска анаграмм.
 *
 * Отдаёт описание UI-полей (выбор режима, выбор языка) и набор пунктов
 * блока доверия для страницы инструмента.
 */
final readonly class AnagramSolverService
{
    /**
     * Возвращает UI-настройки инструмента: режим и язык словаря.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-anagram-mode',
                'label'   => trans('ANAGRAM_SETTING_MODE'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'anagram',     'label' => trans('ANAGRAM_MODE_ANAGRAM'), 'selected' => true],
                    ['value' => 'word-finder', 'label' => trans('ANAGRAM_MODE_WORD_FINDER')],
                    ['value' => 'pattern',     'label' => trans('ANAGRAM_MODE_PATTERN')],
                    ['value' => 'multi-word',  'label' => trans('ANAGRAM_MODE_MULTI_WORD')],
                ],
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-alphabet',
                'label'   => trans('CIPHER_TOOL_SETTING_ALPHABET'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'en', 'label' => trans('LANG_EN'), 'selected' => true],
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
     * Возвращает элементы блока доверия для инструмента поиска анаграмм.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('ANAGRAM_TRUST_TYPE'),
            trans('ANAGRAM_TRUST_LANGUAGES'),
            trans('ANAGRAM_TRUST_MODES'),
            trans('CIPHER_TOOL_TRUST_SERVER'),
        ];
    }
}
