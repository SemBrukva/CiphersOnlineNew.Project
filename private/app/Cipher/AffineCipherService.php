<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис аффинного шифра с поддержкой нескольких алфавитов.
 */
final readonly class AffineCipherService
{
    /**
     * Создаёт экземпляр сервиса аффинного шифра.
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
     * Возвращает UI-настройки инструмента для аффинного шифра.
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
                    ['value' => 'auto', 'label' => trans('CIPHER_TOOL_SETTING_AUTO'), 'attrs' => ['data-max-shift' => 39], 'selected' => true],
                    ['value' => 'en', 'label' => trans('LANG_EN'), 'attrs' => ['data-max-shift' => 25]],
                    ['value' => 'ru', 'label' => trans('LANG_RU'), 'attrs' => ['data-max-shift' => 32]],
                    ['value' => 'es', 'label' => trans('LANG_ES'), 'attrs' => ['data-max-shift' => 26]],
                    ['value' => 'pt', 'label' => trans('LANG_PT'), 'attrs' => ['data-max-shift' => 35]],
                    ['value' => 'tr', 'label' => trans('LANG_TR'), 'attrs' => ['data-max-shift' => 28]],
                    ['value' => 'fr', 'label' => trans('LANG_FR'), 'attrs' => ['data-max-shift' => 39]],
                    ['value' => 'de', 'label' => trans('LANG_DE'), 'attrs' => ['data-max-shift' => 29]],
                    ['value' => 'it', 'label' => trans('LANG_IT'), 'attrs' => ['data-max-shift' => 25]],
                ],
            ],
            [
                'type' => 'text',
                'id' => 'ciphers-key',
                'label' => trans('AFFINE_SETTING_MULTIPLIER'),
                'class' => 'ciphers-settings-input',
                'placeholder' => trans('AFFINE_SETTING_MULTIPLIER_PLACEHOLDER'),
                'value' => '5',
            ],
            [
                'type' => 'number_stepper',
                'id' => 'ciphers-shift',
                'label' => trans('AFFINE_SETTING_SHIFT'),
                'class' => 'ciphers-settings-shift-input',
                'min' => 0,
                'max' => 39,
                'step' => 1,
                'value' => 8,
                'decrementId' => 'ciphers-shift-dec',
                'incrementId' => 'ciphers-shift-inc',
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для аффинного шифра.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('AFFINE_TRUST_SUBSTITUTION'),
            trans('AFFINE_TRUST_KEYS'),
            trans('CIPHER_TOOL_TRUST_MULTI_ALPHA'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
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
     * Возвращает размер выбранного алфавита.
     */
    public function alphabetSize(string $alphabet): int
    {
        return count($this->alphabetCatalog()->alphabet($alphabet));
    }

    /**
     * Проверяет, допустим ли множитель для выбранного алфавита.
     */
    public function isValidMultiplier(int $multiplier, string $alphabet): bool
    {
        $size = $this->alphabetSize($alphabet);

        return $multiplier > 0 && $multiplier < $size && $this->gcd($multiplier, $size) === 1;
    }

    /**
     * Выполняет шифрование/дешифрование текста аффинным шифром.
     */
    public function process(string $text, string $alphabet, int $multiplier, int $shift, string $direction): string
    {
        $alphabetData = $this->alphabetCatalog()->alphabet($alphabet);
        $alphabetSize = count($alphabetData);
        $upperAlphabetData = array_map('mb_strtoupper', $alphabetData);
        $indexMapLower = array_flip($alphabetData);
        $indexMapUpper = array_flip($upperAlphabetData);
        $normalizedShift = (($shift % $alphabetSize) + $alphabetSize) % $alphabetSize;
        $inverseMultiplier = $direction === 'decrypt' ? $this->modInverse($multiplier, $alphabetSize) : null;
        $output = '';
        $length = mb_strlen($text);

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($text, $i, 1);
            $lower = mb_strtolower($char);

            if (!isset($indexMapLower[$lower])) {
                $output .= $char;
                continue;
            }

            $index = (int) $indexMapLower[$lower];
            if ($direction === 'decrypt') {
                $nextIndex = (($inverseMultiplier ?? 0) * ($index - $normalizedShift)) % $alphabetSize;
                $nextIndex = ($nextIndex + $alphabetSize) % $alphabetSize;
            } else {
                $nextIndex = (($multiplier * $index) + $normalizedShift) % $alphabetSize;
            }

            if (isset($indexMapUpper[$char])) {
                $output .= $upperAlphabetData[$nextIndex];
                continue;
            }

            $output .= $alphabetData[$nextIndex];
        }

        return $output;
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

    /**
     * Вычисляет наибольший общий делитель.
     */
    private function gcd(int $left, int $right): int
    {
        $left = abs($left);
        $right = abs($right);

        while ($right !== 0) {
            $next = $left % $right;
            $left = $right;
            $right = $next;
        }

        return $left;
    }

    /**
     * Возвращает модульную обратную величину.
     */
    private function modInverse(int $value, int $modulus): int
    {
        $value = (($value % $modulus) + $modulus) % $modulus;

        for ($candidate = 1; $candidate < $modulus; $candidate++) {
            if (($value * $candidate) % $modulus === 1) {
                return $candidate;
            }
        }

        return 0;
    }
}
