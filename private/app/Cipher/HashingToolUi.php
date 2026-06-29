<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Конфигурация tool_ui для инструментов категории «hashing».
 * Используется и на странице конкретного инструмента, и в hero-блоке категории.
 */
final class HashingToolUi
{
    /**
     * Возвращает алгоритм по умолчанию для slug-инструмента.
     * Для slug без явного соответствия (универсальная страница) — SHA-256.
     */
    public static function defaultAlgorithm(string $cipherAlias): string
    {
        return match ($cipherAlias) {
            'md5'      => 'md5',
            'crc32'    => 'crc32',
            'sha1'     => 'sha-1',
            'sha384'   => 'sha-384',
            'sha512'   => 'sha-512',
            'sha3-256' => 'sha3-256',
            'sha3-384' => 'sha3-384',
            'sha3-512' => 'sha3-512',
            'blake2b'  => 'blake2b',
            'blake2s'  => 'blake2s',
            default    => 'sha-256',
        };
    }

    /**
     * Дополняет базовый tool_ui hashing-специфичными полями: oneWayMode, labels, select алгоритмов.
     *
     * @param  array<string, mixed> $baseToolUi
     * @return array<string, mixed>
     */
    public static function apply(array $baseToolUi, string $cipherAlias): array
    {
        $baseToolUi['oneWayMode']        = true;
        $baseToolUi['tabEncode']         = trans('HASH_TAB_HASH');
        $baseToolUi['inputLabelEncode']  = trans('HASH_INPUT_LABEL');
        $baseToolUi['placeholderEncode'] = trans('HASH_PLACEHOLDER_INPUT');
        $baseToolUi['resultLabel']       = trans('HASH_RESULT_LABEL');

        $algorithms = [
            'sha-256'  => 'SHA-256',
            'sha-1'    => 'SHA-1',
            'sha-384'  => 'SHA-384',
            'sha-512'  => 'SHA-512',
            'sha3-256' => 'SHA3-256',
            'sha3-384' => 'SHA3-384',
            'sha3-512' => 'SHA3-512',
            'blake2b'  => 'BLAKE2b',
            'blake2s'  => 'BLAKE2s',
            'md5'      => 'MD5',
            'crc32'    => 'CRC32',
        ];
        $selectedAlgorithm = self::defaultAlgorithm($cipherAlias);

        $options = [];
        foreach ($algorithms as $value => $label) {
            $options[] = [
                'value'    => $value,
                'label'    => $label,
                'selected' => $value === $selectedAlgorithm,
            ];
        }

        $baseToolUi['settings'] = array_merge([[
            'type'    => 'select',
            'id'      => 'ciphers-hash-algorithm',
            'label'   => trans('HASH_ALGORITHM_LABEL'),
            'options' => $options,
        ]], $baseToolUi['settings'] ?? []);

        return $baseToolUi;
    }
}
