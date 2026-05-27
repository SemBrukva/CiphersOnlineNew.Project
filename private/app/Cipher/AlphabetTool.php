<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Общие операции над алфавитами для cipher-сервисов.
 */
final readonly class AlphabetTool
{
    /**
     * Создаёт экземпляр утилиты алфавитов.
     */
    public function __construct(
        private AlphabetCatalog $catalog
    ) {
    }

    /**
     * Проверяет, содержит ли текст хотя бы один символ выбранного алфавита.
     */
    public function hasAlphabetCharacters(string $text, string $alphabet): bool
    {
        $letters = $this->catalog->alphabet($alphabet);
        $set = array_flip($letters);

        $length = mb_strlen($text);
        for ($i = 0; $i < $length; $i++) {
            $char = mb_strtolower(mb_substr($text, $i, 1));
            if (isset($set[$char])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Автоопределяет алфавит по количеству совпадений букв в тексте.
     */
    public function detectAlphabet(string $text): string
    {
        $scores = [];
        foreach ($this->catalog->all() as $code => $letters) {
            $set = array_flip($letters);
            $scores[$code] = 0;

            $length = mb_strlen($text);
            for ($i = 0; $i < $length; $i++) {
                $char = mb_strtolower(mb_substr($text, $i, 1));
                if (isset($set[$char])) {
                    $scores[$code]++;
                }
            }
        }

        $maxScore = max($scores);
        if ($maxScore === 0) {
            return 'en';
        }

        foreach (['ru', 'tr', 'de', 'fr', 'pt', 'es', 'it', 'en'] as $code) {
            if (($scores[$code] ?? 0) === $maxScore) {
                return $code;
            }
        }

        arsort($scores);

        return (string) array_key_first($scores);
    }
}

