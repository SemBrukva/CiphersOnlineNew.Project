<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Конфигурация tool_ui для HMAC-инструмента.
 * Имеет два инпута (text + key), селекторы алгоритма и формата ключа.
 */
final class HmacToolUi
{
    /**
     * Дополняет базовый tool_ui HMAC-специфичными полями.
     *
     * @param  array<string, mixed> $baseToolUi
     * @return array<string, mixed>
     */
    public static function apply(array $baseToolUi): array
    {
        $baseToolUi['oneWayMode']        = true;
        $baseToolUi['hmacMode']          = true;
        $baseToolUi['tabEncode']         = trans('HASH_TAB_HASH');
        $baseToolUi['inputLabelEncode']  = trans('HASH_INPUT_LABEL');
        $baseToolUi['placeholderEncode'] = trans('HASH_PLACEHOLDER_INPUT');
        $baseToolUi['resultLabel']       = trans('HASH_RESULT_LABEL');

        $algorithms = [
            'hmac-sha-256' => 'HMAC-SHA-256',
            'hmac-sha-1'   => 'HMAC-SHA-1',
            'hmac-sha-384' => 'HMAC-SHA-384',
            'hmac-sha-512' => 'HMAC-SHA-512',
        ];

        $algorithmOptions = [];
        foreach ($algorithms as $value => $label) {
            $algorithmOptions[] = [
                'value'    => $value,
                'label'    => $label,
                'selected' => $value === 'hmac-sha-256',
            ];
        }

        $keyFormats = [
            'text'   => trans('HASH_HMAC_KEY_FORMAT_TEXT'),
            'hex'    => trans('HASH_HMAC_KEY_FORMAT_HEX'),
            'base64' => trans('HASH_HMAC_KEY_FORMAT_BASE64'),
        ];

        $keyFormatOptions = [];
        foreach ($keyFormats as $value => $label) {
            $keyFormatOptions[] = [
                'value'    => $value,
                'label'    => $label,
                'selected' => $value === 'text',
            ];
        }

        $baseToolUi['settings'] = [
            [
                'type'    => 'select',
                'id'      => 'ciphers-hash-algorithm',
                'label'   => trans('HASH_ALGORITHM_LABEL'),
                'options' => $algorithmOptions,
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-hmac-key-format',
                'label'   => trans('HASH_HMAC_KEY_FORMAT_LABEL'),
                'options' => $keyFormatOptions,
            ],
            [
                'type'        => 'textarea',
                'id'          => 'ciphers-key',
                'label'       => trans('HASH_HMAC_KEY_LABEL'),
                'placeholder' => trans('HASH_HMAC_KEY_PLACEHOLDER'),
                'encodeOnly'  => false,
            ],
        ];

        return $baseToolUi;
    }
}
