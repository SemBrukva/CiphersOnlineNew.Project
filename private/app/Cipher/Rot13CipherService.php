<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис ROT13-преобразования для латинского алфавита.
 */
final readonly class Rot13CipherService
{
    /**
     * Возвращает UI-настройки инструмента ROT13.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [];
    }

    /**
     * Возвращает элементы блока доверия для ROT13.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('ROT13_TRUST_TYPE'),
            trans('ROT13_TRUST_KEYLESS'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }

    /**
     * Проверяет, содержит ли текст латинские буквы.
     */
    public function hasLatinCharacters(string $text): bool
    {
        return preg_match('/[A-Za-z]/', $text) === 1;
    }

    /**
     * Выполняет ROT13-преобразование текста.
     */
    public function process(string $text): string
    {
        $output = '';
        $length = strlen($text);

        for ($i = 0; $i < $length; $i++) {
            $code = ord($text[$i]);

            if ($code >= 65 && $code <= 90) {
                $output .= chr((($code - 65 + 13) % 26) + 65);
                continue;
            }

            if ($code >= 97 && $code <= 122) {
                $output .= chr((($code - 97 + 13) % 26) + 97);
                continue;
            }

            $output .= $text[$i];
        }

        return $output;
    }
}
