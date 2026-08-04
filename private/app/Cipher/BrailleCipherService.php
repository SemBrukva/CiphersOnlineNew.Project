<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента шрифта Брайля (Grade 1).
 */
final readonly class BrailleCipherService
{
    /**
     * Возвращает UI-настройки: язык, формат вывода и обработку регистра.
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
            [
                'type'    => 'select',
                'id'      => 'ciphers-braille-format',
                'label'   => trans('BRAILLE_SETTING_FORMAT'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'unicode', 'label' => trans('BRAILLE_FORMAT_UNICODE'), 'selected' => true],
                    ['value' => 'dots', 'label' => trans('BRAILLE_FORMAT_DOTS')],
                    ['value' => 'ascii', 'label' => trans('BRAILLE_FORMAT_ASCII')],
                ],
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-braille-case',
                'label'   => trans('BRAILLE_SETTING_CASE'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'keep', 'label' => trans('BRAILLE_CASE_KEEP'), 'selected' => true],
                    ['value' => 'ignore', 'label' => trans('BRAILLE_CASE_IGNORE')],
                ],
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для шрифта Брайля.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('BRAILLE_TRUST_STANDARD'),
            trans('BRAILLE_TRUST_KEYLESS'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
