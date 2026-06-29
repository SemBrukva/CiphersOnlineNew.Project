<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Конфигурация tool_ui для KDF-инструментов (PBKDF2, bcrypt, Argon2).
 * Каждый KDF имеет свой набор параметров и общий UX: вкладки Hash / Verify,
 * кнопка Compute (manualRun), поле hash для verify-режима.
 */
final class KdfToolUi
{
    /**
     * Дополняет базовый tool_ui KDF-специфичными полями.
     *
     * @param  array<string, mixed> $baseToolUi
     * @return array<string, mixed>
     */
    public static function apply(array $baseToolUi, string $cipherAlias): array
    {
        $baseToolUi['kdfMode']            = true;
        $baseToolUi['kdfVerifyMode']      = true;
        $baseToolUi['manualRun']          = true;
        $baseToolUi['disableLiveMode']    = true;
        $baseToolUi['tabEncode']          = trans('KDF_TAB_HASH');
        $baseToolUi['tabDecode']          = trans('KDF_TAB_VERIFY');
        $baseToolUi['inputLabelEncode']   = trans('KDF_INPUT_LABEL');
        $baseToolUi['inputLabelDecode']   = trans('KDF_INPUT_LABEL');
        $baseToolUi['placeholderEncode']  = trans('KDF_PLACEHOLDER_INPUT');
        $baseToolUi['placeholderDecode']  = trans('KDF_PLACEHOLDER_INPUT');
        $baseToolUi['resultLabel']        = trans('KDF_RESULT_LABEL');
        $baseToolUi['runLabel']           = trans('KDF_COMPUTE_LABEL');
        $baseToolUi['kdfVerifyHashLabel'] = trans('KDF_VERIFY_HASH_LABEL');
        $baseToolUi['kdfVerifyHashPlaceholder'] = trans('KDF_VERIFY_HASH_PLACEHOLDER');

        $baseToolUi['settings'] = match ($cipherAlias) {
            'pbkdf2' => self::pbkdf2Settings(),
            'bcrypt' => self::bcryptSettings(),
            'argon2' => self::argon2Settings(),
            default  => $baseToolUi['settings'] ?? [],
        };

        return $baseToolUi;
    }

    /**
     * Settings для PBKDF2: hash function, iterations, key length, salt.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function pbkdf2Settings(): array
    {
        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-kdf-hash',
                'label'   => trans('KDF_HASH_LABEL'),
                'options' => [
                    ['value' => 'SHA-256', 'label' => 'SHA-256', 'selected' => true],
                    ['value' => 'SHA-512', 'label' => 'SHA-512', 'selected' => false],
                    ['value' => 'SHA-1',   'label' => 'SHA-1',   'selected' => false],
                ],
            ],
            [
                'type'        => 'text',
                'id'          => 'ciphers-kdf-iterations',
                'label'       => trans('KDF_ITERATIONS_LABEL'),
                'placeholder' => '600000',
                'value'       => '600000',
                'class'       => 'ciphers-settings-input ciphers-settings-input--number',
            ],
            [
                'type'        => 'text',
                'id'          => 'ciphers-kdf-key-length',
                'label'       => trans('KDF_KEY_LENGTH_LABEL'),
                'placeholder' => '32',
                'value'       => '32',
                'class'       => 'ciphers-settings-input ciphers-settings-input--number',
            ],
            [
                'type'        => 'textarea',
                'id'          => 'ciphers-kdf-salt',
                'label'       => trans('KDF_SALT_LABEL'),
                'placeholder' => trans('KDF_SALT_PLACEHOLDER'),
            ],
        ];
    }

    /**
     * Settings для bcrypt: cost factor (только; salt включён в hash).
     *
     * @return array<int, array<string, mixed>>
     */
    private static function bcryptSettings(): array
    {
        return [
            [
                'type'        => 'text',
                'id'          => 'ciphers-kdf-cost',
                'label'       => trans('KDF_BCRYPT_COST_LABEL'),
                'placeholder' => '12',
                'value'       => '12',
                'class'       => 'ciphers-settings-input ciphers-settings-input--number',
            ],
        ];
    }

    /**
     * Settings для Argon2id: variant, memory, iterations, parallelism, key length, salt.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function argon2Settings(): array
    {
        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-kdf-variant',
                'label'   => trans('KDF_ARGON2_VARIANT_LABEL'),
                'options' => [
                    ['value' => 'argon2id', 'label' => 'Argon2id', 'selected' => true],
                    ['value' => 'argon2i',  'label' => 'Argon2i',  'selected' => false],
                    ['value' => 'argon2d',  'label' => 'Argon2d',  'selected' => false],
                ],
            ],
            [
                'type'        => 'text',
                'id'          => 'ciphers-kdf-memory',
                'label'       => trans('KDF_ARGON2_MEMORY_LABEL'),
                'placeholder' => '19456',
                'value'       => '19456',
                'class'       => 'ciphers-settings-input ciphers-settings-input--number',
            ],
            [
                'type'        => 'text',
                'id'          => 'ciphers-kdf-iterations',
                'label'       => trans('KDF_ITERATIONS_LABEL'),
                'placeholder' => '2',
                'value'       => '2',
                'class'       => 'ciphers-settings-input ciphers-settings-input--number',
            ],
            [
                'type'        => 'text',
                'id'          => 'ciphers-kdf-parallelism',
                'label'       => trans('KDF_ARGON2_PARALLELISM_LABEL'),
                'placeholder' => '1',
                'value'       => '1',
                'class'       => 'ciphers-settings-input ciphers-settings-input--number',
            ],
            [
                'type'        => 'text',
                'id'          => 'ciphers-kdf-key-length',
                'label'       => trans('KDF_KEY_LENGTH_LABEL'),
                'placeholder' => '32',
                'value'       => '32',
                'class'       => 'ciphers-settings-input ciphers-settings-input--number',
            ],
            [
                'type'        => 'textarea',
                'id'          => 'ciphers-kdf-salt',
                'label'       => trans('KDF_SALT_LABEL'),
                'placeholder' => trans('KDF_SALT_PLACEHOLDER'),
            ],
        ];
    }
}
