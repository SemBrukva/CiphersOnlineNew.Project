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
