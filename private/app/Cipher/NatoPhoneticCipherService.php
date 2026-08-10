<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента «Фонетический алфавит NATO».
 */
final readonly class NatoPhoneticCipherService
{
    /**
     * Возвращает UI-настройки: вариант алфавита, разделитель и формат вывода.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-nato-variant',
                'label'   => trans('NATO_SETTING_VARIANT'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'nato', 'label' => trans('NATO_VARIANT_NATO'), 'selected' => true],
                    ['value' => 'aviation', 'label' => trans('NATO_VARIANT_AVIATION')],
                    ['value' => 'police', 'label' => trans('NATO_VARIANT_POLICE')],
                    ['value' => 'german', 'label' => trans('NATO_VARIANT_GERMAN')],
                ],
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-nato-separator',
                'label'   => trans('NATO_SETTING_SEPARATOR'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'space', 'label' => trans('NATO_SEP_SPACE'), 'selected' => true],
                    ['value' => 'hyphen', 'label' => trans('NATO_SEP_HYPHEN')],
                    ['value' => 'comma', 'label' => trans('NATO_SEP_COMMA')],
                    ['value' => 'newline', 'label' => trans('NATO_SEP_NEWLINE')],
                ],
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-nato-show-letter',
                'label'   => trans('NATO_SETTING_SHOW_LETTER'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'words', 'label' => trans('NATO_SHOW_WORDS'), 'selected' => true],
                    ['value' => 'pairs', 'label' => trans('NATO_SHOW_PAIRS')],
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
            trans('NATO_TRUST_STANDARD'),
            trans('NATO_TRUST_KEYLESS'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
