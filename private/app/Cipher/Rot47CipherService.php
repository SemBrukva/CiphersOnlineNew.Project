<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра ROT47.
 *
 * ROT47 — обобщение ROT13 на печатный диапазон ASCII (коды 33–126, 94 символа):
 * каждый символ сдвигается на 47 позиций внутри этого диапазона. Символы вне
 * диапазона (пробел, перевод строки, байты UTF-8 > 126) не меняются. Шифр
 * само­обратный: применение дважды возвращает исходный текст (47 + 47 ≡ 0 mod 94).
 */
final readonly class Rot47CipherService
{
    /** Нижняя граница печатного диапазона ASCII. */
    private const int RANGE_START = 33;

    /** Размер печатного диапазона ASCII (33–126). */
    private const int RANGE_SIZE = 94;

    /** Величина сдвига. */
    private const int SHIFT = 47;

    /**
     * Возвращает UI-настройки инструмента ROT47.
     *
     * ROT47 работает по кодам печатного ASCII, а не по конкретному алфавиту,
     * поэтому селект языка не нужен и список настроек пуст.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [];
    }

    /**
     * Возвращает элементы блока доверия для ROT47.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('ROT47_TRUST_TYPE'),
            trans('ROT47_TRUST_KEYLESS'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }

    /**
     * Проверяет, содержит ли текст хотя бы один печатный ASCII-символ (коды 33–126),
     * который ROT47 действительно преобразует.
     */
    public function hasPrintableAscii(string $text): bool
    {
        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $code = ord($text[$i]);
            if ($code >= self::RANGE_START && $code <= self::RANGE_START + self::RANGE_SIZE - 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Применяет преобразование ROT47 к тексту (шифрование и дешифрование совпадают).
     */
    public function process(string $text): string
    {
        $out = '';
        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $code = ord($text[$i]);
            if ($code >= self::RANGE_START && $code <= self::RANGE_START + self::RANGE_SIZE - 1) {
                $out .= chr(self::RANGE_START + (($code - self::RANGE_START + self::SHIFT) % self::RANGE_SIZE));
            } else {
                $out .= $text[$i];
            }
        }

        return $out;
    }
}
