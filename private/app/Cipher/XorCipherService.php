<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис XOR-шифра.
 */
final class XorCipherService
{
    /**
     * Создаёт экземпляр сервиса XOR-шифра.
     */
    public function __construct()
    {
    }

    /**
     * Возвращает UI-настройки инструмента для XOR-шифра.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type'        => 'textarea',
                'id'          => 'ciphers-key',
                'label'       => trans('CIPHER_TOOL_SETTING_KEY'),
                'class'       => 'ciphers-settings-textarea',
                'placeholder' => trans('CIPHER_TOOL_SETTING_KEY_PLACEHOLDER'),
                'value'       => '',
                'hint'        => trans('XOR_KEY_HINT'),
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-xor-key-format',
                'label'   => trans('XOR_KEY_FORMAT_LABEL'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'text', 'label' => trans('XOR_KEY_FORMAT_TEXT'), 'selected' => true],
                    ['value' => 'hex',  'label' => trans('XOR_KEY_FORMAT_HEX')],
                ],
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для XOR-шифра.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('XOR_TRUST_SYMMETRIC'),
            trans('XOR_TRUST_KEY_CYCLING'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }

    /**
     * Выполняет шифрование или дешифрование XOR-шифром.
     *
     * При шифровании возвращает uppercase hex-строку.
     * При дешифровании принимает hex-строку и возвращает исходный текст.
     *
     * @param string $keyFormat  'text' — ключ как UTF-8 строка, 'hex' — ключ как hex-последовательность байт.
     */
    public function process(string $text, string $key, string $direction, string $keyFormat = 'text'): string
    {
        if ($key === '') {
            return $text;
        }

        $keyBytes = $this->resolveKeyBytes($key, $keyFormat);
        if ($keyBytes === '') {
            return '';
        }

        return $direction === 'decrypt'
            ? $this->decrypt($text, $keyBytes)
            : $this->encrypt($text, $keyBytes);
    }

    /**
     * Декодирует ключ в байты согласно формату.
     */
    public function resolveKeyBytes(string $key, string $keyFormat): string
    {
        if ($keyFormat !== 'hex') {
            return $key;
        }

        $hex   = preg_replace('/[^0-9a-fA-F]/', '', $key);
        $bytes = ($hex !== null && $hex !== '' && strlen($hex) % 2 === 0) ? hex2bin($hex) : false;

        return $bytes !== false ? $bytes : '';
    }

    /**
     * Шифрует текст: XOR по байтам с циклическим ключом, результат — hex.
     */
    private function encrypt(string $text, string $keyBytes): string
    {
        return strtoupper(bin2hex($this->xorBytes($text, $keyBytes)));
    }

    /**
     * Дешифрует hex-строку с помощью XOR и того же ключа.
     */
    private function decrypt(string $text, string $keyBytes): string
    {
        $hex = preg_replace('/[^0-9a-fA-F]/', '', $text);

        if ($hex === null || $hex === '' || strlen($hex) % 2 !== 0) {
            return '';
        }

        $bytes = hex2bin($hex);
        if ($bytes === false) {
            return '';
        }

        return $this->xorBytes($bytes, $keyBytes);
    }

    /**
     * Применяет побайтовый XOR между входом и циклическим ключом.
     */
    private function xorBytes(string $text, string $keyBytes): string
    {
        $result     = '';
        $textLength = strlen($text);
        $keyLength  = strlen($keyBytes);

        for ($i = 0; $i < $textLength; $i++) {
            $result .= $text[$i] ^ $keyBytes[$i % $keyLength];
        }

        return $result;
    }
}
