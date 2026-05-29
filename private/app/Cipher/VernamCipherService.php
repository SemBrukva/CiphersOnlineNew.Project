<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Вернама.
 */
final class VernamCipherService
{
    /**
     * Создаёт экземпляр сервиса шифра Вернама.
     */
    public function __construct()
    {
    }

    /**
     * Возвращает UI-настройки инструмента для шифра Вернама.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type' => 'text',
                'id' => 'ciphers-key',
                'label' => trans('CIPHER_TOOL_SETTING_KEY'),
                'class' => 'ciphers-settings-input',
                'placeholder' => trans('CIPHER_TOOL_SETTING_KEY_PLACEHOLDER'),
                'value' => '',
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для шифра Вернама.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('VERNAM_TRUST_OTP'),
            trans('VERNAM_TRUST_KEY_LENGTH'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }

    /**
     * Выполняет шифрование/дешифрование текста по Вернаму.
     */
    public function process(string $text, string $key, string $direction): string
    {
        if ($key === '') {
            return $text;
        }

        return $direction === 'decrypt'
            ? $this->decrypt($text, $key)
            : $this->encrypt($text, $key);
    }

    /**
     * Шифрует текст по Вернаму и кодирует результат в base64.
     */
    private function encrypt(string $text, string $key): string
    {
        return base64_encode($this->xorBytes($text, $key));
    }

    /**
     * Дешифрует base64-строку по Вернаму.
     */
    private function decrypt(string $text, string $key): string
    {
        $decoded = base64_decode($text, true);

        if ($decoded === false) {
            return '';
        }

        return $this->xorBytes($decoded, $key);
    }

    /**
     * Применяет побайтовый XOR между входом и циклическим ключом.
     */
    private function xorBytes(string $text, string $key): string
    {
        $result = '';
        $textLength = strlen($text);
        $keyLength = strlen($key);

        for ($i = 0; $i < $textLength; $i++) {
            $result .= $text[$i] ^ $key[$i % $keyLength];
        }

        return $result;
    }
}
