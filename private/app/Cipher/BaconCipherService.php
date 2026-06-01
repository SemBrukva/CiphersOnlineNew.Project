<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Бэкона с поддержкой нескольких алфавитов.
 */
final readonly class BaconCipherService
{
    /**
     * Создаёт экземпляр сервиса шифра Бэкона.
     */
    public function __construct(
        private ?AlphabetCatalog $catalog = null,
        private ?AlphabetTool    $alphabetTool = null
    ) {
    }

    /**
     * Возвращает список поддерживаемых кодов алфавитов.
     *
     * @return string[]
     */
    public function supportedAlphabetCodes(): array
    {
        return $this->alphabetCatalog()->codes();
    }

    /**
     * Возвращает UI-настройки инструмента для шифра Бэкона.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type' => 'select',
                'id' => 'ciphers-alphabet',
                'label' => trans('CIPHER_TOOL_SETTING_ALPHABET'),
                'class' => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'auto', 'label' => trans('CIPHER_TOOL_SETTING_AUTO'), 'selected' => true],
                    ['value' => 'en', 'label' => 'English'],
                    ['value' => 'ru', 'label' => 'Русский'],
                    ['value' => 'es', 'label' => 'Español'],
                    ['value' => 'pt', 'label' => 'Português'],
                    ['value' => 'tr', 'label' => 'Türkçe'],
                    ['value' => 'fr', 'label' => 'Français'],
                    ['value' => 'de', 'label' => 'Deutsch'],
                    ['value' => 'it', 'label' => 'Italiano'],
                ],
            ],
            [
                'type' => 'textarea',
                'id' => 'ciphers-cover',
                'label' => trans('BACON_COVER_LABEL'),
                'class' => 'ciphers-settings-textarea',
                'placeholder' => trans('BACON_COVER_PLACEHOLDER'),
                'value' => '',
                'hint' => trans('BACON_COVER_HINT'),
                'encodeOnly' => true,
                'showCapacity' => true,
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для шифра Бэкона.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('BACON_TRUST_STEGO'),
            trans('BACON_TRUST_BINARY'),
            trans('BACON_TRUST_STEGANOGRAPHY'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
        ];
    }

    /**
     * Проверяет, содержит ли текст хотя бы один символ выбранного алфавита.
     */
    public function hasAlphabetCharacters(string $text, string $alphabet): bool
    {
        return $this->tool()->hasAlphabetCharacters($text, $alphabet);
    }

    /**
     * Автоопределяет алфавит по количеству совпадений букв в тексте.
     */
    public function detectAlphabet(string $text): string
    {
        return $this->tool()->detectAlphabet($text);
    }

    /**
     * Выполняет шифрование/дешифрование текста по Бэкону.
     */
    public function process(string $text, string $alphabet, string $direction): string
    {
        return $direction === 'decrypt'
            ? $this->decrypt($text, $alphabet)
            : $this->encrypt($text, $alphabet);
    }

    /**
     * Возвращает true, если текст является стеганографическим (не классический A/B-формат).
     */
    public function isStegoText(string $text): bool
    {
        return preg_match('/[^AB\s]/iu', $text) === 1;
    }

    /**
     * Подсчитывает количество Unicode-букв в тексте (для проверки длины cover-текста).
     */
    public function countLetters(string $text): int
    {
        return (int) preg_match_all('/\p{L}/u', $text);
    }

    /**
     * Подсчитывает количество символов текста, входящих в заданный алфавит.
     */
    public function countAlphabetChars(string $text, string $alphabet): int
    {
        $alphabetData = $this->alphabetCatalog()->alphabet(mb_strtolower(trim($alphabet)));
        $indexMap = array_flip($alphabetData);
        $count = 0;

        foreach (mb_str_split(mb_strtolower($text)) as $char) {
            $normalized = $char === 'ё' ? 'е' : $char;
            if (isset($indexMap[$normalized])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Возвращает фактическое количество A/B-бит при кодировании текста.
     * Учитывает, что для алфавитов с индексами > 31 генерируется более 5 бит на символ.
     */
    public function encodedBitCount(string $text, string $alphabet): int
    {
        return mb_strlen((string) preg_replace('/[^AB]/i', '', $this->encrypt($text, $alphabet)));
    }

    /**
     * Кодирует секретное сообщение в cover-текст через регистр букв (стеганография Бэкона).
     *
     * Первые 10 букв cover-текста образуют 10-битный заголовок с длиной A/B-потока
     * (старшие 5 бит, затем младшие 5 бит). Это позволяет декодеру точно остановиться
     * после нужного количества бит и не захватывать «лишние» буквы.
     *
     * Заглавная буква cover-текста = B, строчная = A.
     */
    public function steganographyEncrypt(string $secret, string $coverText, string $alphabet): string
    {
        $bodyAb = (string) preg_replace('/[^AB]/i', '', $this->encrypt($secret, $alphabet));
        $totalBits = mb_strlen($bodyAb);

        // 10-битный заголовок: старшие 5 бит + младшие 5 бит числа totalBits
        $headerHigh = str_pad(decbin(($totalBits >> 5) & 0x1f), 5, '0', STR_PAD_LEFT);
        $headerLow  = str_pad(decbin($totalBits & 0x1f),        5, '0', STR_PAD_LEFT);
        $allBits = mb_str_split(
            mb_strtoupper(strtr($headerHigh . $headerLow . $bodyAb, ['0' => 'A', '1' => 'B']))
        );

        $bitCount = count($allBits);
        $bitIndex = 0;

        $result = '';
        foreach (mb_str_split($coverText) as $char) {
            if ($bitIndex >= $bitCount) {
                $result .= $char;
                continue;
            }

            if (preg_match('/\p{L}/u', $char) === 1) {
                $result .= $allBits[$bitIndex] === 'B'
                    ? mb_strtoupper($char)
                    : mb_strtolower($char);
                $bitIndex++;
            } else {
                $result .= $char;
            }
        }

        return $result;
    }

    /**
     * Декодирует стеганографический текст Бэкона: читает 10-битный заголовок длины,
     * затем извлекает ровно столько A/B-бит, сколько закодировано, и декодирует секрет.
     */
    public function steganographyDecrypt(string $stegoText, string $alphabet): string
    {
        $allBits = [];
        foreach (mb_str_split($stegoText) as $char) {
            if (preg_match('/\p{L}/u', $char) === 1) {
                $allBits[] = mb_strtoupper($char) === $char ? 'B' : 'A';
            }
        }

        if (count($allBits) < 10) {
            return '';
        }

        // Читаем 10-битный заголовок
        $headerHigh = bindec(strtr(implode('', array_slice($allBits, 0, 5)), ['A' => '0', 'B' => '1']));
        $headerLow  = bindec(strtr(implode('', array_slice($allBits, 5, 5)), ['A' => '0', 'B' => '1']));
        $totalBits  = ((int) $headerHigh << 5) | (int) $headerLow;

        if ($totalBits === 0 || count($allBits) < 10 + $totalBits) {
            return '';
        }

        $bodyBits = implode('', array_slice($allBits, 10, $totalBits));

        return $this->decrypt($bodyBits, $alphabet);
    }

    /**
     * Кодирует текст в группы A/B.
     */
    private function encrypt(string $text, string $alphabet): string
    {
        $alphabetData = $this->alphabetCatalog()->alphabet(mb_strtolower(trim($alphabet)));
        $indexMap = array_flip($alphabetData);
        $chars = mb_str_split($text);
        $result = '';

        foreach ($chars as $char) {
            $normalizedChar = mb_strtolower($char === 'ё' ? 'е' : $char);

            if (isset($indexMap[$normalizedChar])) {
                $binary = str_pad(decbin((int) $indexMap[$normalizedChar]), 5, '0', STR_PAD_LEFT);
                $result .= strtr($binary, ['0' => 'A', '1' => 'B']);
                continue;
            }

            if (preg_match('/\s/u', $char) === 1) {
                $result .= ' ';
            }
        }

        return $result;
    }

    /**
     * Декодирует группы A/B в текст.
     */
    private function decrypt(string $text, string $alphabet): string
    {
        $alphabetData = $this->alphabetCatalog()->alphabet(mb_strtolower(trim($alphabet)));
        $chars = mb_str_split(mb_strtoupper($text));
        $groups = [];
        $buffer = '';
        $pendingSpace = false;

        foreach ($chars as $char) {
            if ($char === 'A' || $char === 'B') {
                if (mb_strlen($buffer) === 5) {
                    $groups[] = $buffer;
                    $buffer = '';

                    if ($pendingSpace) {
                        $groups[] = ' ';
                        $pendingSpace = false;
                    }
                }

                $buffer .= $char;
                continue;
            }

            if (preg_match('/\s/u', $char) === 1) {
                $pendingSpace = true;
            }
        }

        if (mb_strlen($buffer) === 5) {
            $groups[] = $buffer;
        }

        $result = '';
        $maxIndex = count($alphabetData) - 1;

        foreach ($groups as $group) {
            if ($group === ' ') {
                $result .= ' ';
                continue;
            }

            $index = bindec(strtr($group, ['A' => '0', 'B' => '1']));
            if ($index < 0 || $index > $maxIndex) {
                continue;
            }

            $result .= $alphabetData[$index];
        }

        return $result;
    }

    /**
     * Возвращает каталог алфавитов.
     */
    private function alphabetCatalog(): AlphabetCatalog
    {
        return $this->catalog ?? new AlphabetCatalog();
    }

    /**
     * Возвращает утилиту общих операций с алфавитами.
     */
    private function tool(): AlphabetTool
    {
        return $this->alphabetTool ?? new AlphabetTool($this->alphabetCatalog());
    }
}
