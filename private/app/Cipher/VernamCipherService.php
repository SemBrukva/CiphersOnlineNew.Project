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
                'label' => locale() === 'ru' ? 'Ключ' : 'Key',
                'class' => 'ciphers-settings-select',
                'placeholder' => locale() === 'ru' ? 'Введите ключ' : 'Enter key',
                'value' => '',
            ],
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
