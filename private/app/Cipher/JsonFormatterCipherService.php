<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента JSON Formatter / Validator.
 */
final readonly class JsonFormatterCipherService
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
                'id'      => 'ciphers-json-indent',
                'label'   => trans('JSON_FORMATTER_SETTING_INDENT'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => '2',   'label' => trans('JSON_FORMATTER_INDENT_2'), 'selected' => true],
                    ['value' => '4',   'label' => trans('JSON_FORMATTER_INDENT_4')],
                    ['value' => 'tab', 'label' => trans('JSON_FORMATTER_INDENT_TAB')],
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
            trans('JSON_FORMATTER_TRUST_VALIDATES'),
            trans('JSON_FORMATTER_TRUST_FORMATS'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
