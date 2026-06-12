<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента "Числа ↔ Буквы".
 */
final readonly class NumbersToLettersService
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
                'id'      => 'ciphers-n2l-type',
                'label'   => trans('NUM2LET_SETTING_TYPE_LABEL'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'positional-1', 'label' => trans('NUM2LET_TYPE_POSITIONAL_1'), 'selected' => true],
                    ['value' => 'positional-0', 'label' => trans('NUM2LET_TYPE_POSITIONAL_0')],
                    ['value' => 'ascii',         'label' => trans('NUM2LET_TYPE_ASCII')],
                    ['value' => 'hex',           'label' => trans('NUM2LET_TYPE_HEX')],
                    ['value' => 'binary',        'label' => trans('NUM2LET_TYPE_BINARY')],
                ],
            ],
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
                'id'      => 'ciphers-delimiter',
                'label'   => trans('CIPHER_TOOL_SETTING_DELIMITER'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'space',  'label' => trans('CIPHER_TOOL_SETTING_SPACE'), 'selected' => true],
                    ['value' => 'dash',   'label' => '-'],
                    ['value' => 'comma',  'label' => ','],
                    ['value' => 'slash',  'label' => '/'],
                    ['value' => 'dot',    'label' => '.'],
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
            trans('NUM2LET_TRUST_MODES'),
            trans('NUM2LET_TRUST_MULTILANG'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
