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
     *
     * CaseFolder опционален для обратной совместимости со старыми вызовами без DI;
     * при отсутствии используется fallback-инстанс. Производственный путь — через
     * Container, который всегда подставит реальный сервис.
     */
    public function __construct(
        private AlphabetCatalog $catalog,
        private ?CaseFolder     $caseFolder = null
    ) {
    }

    /**
     * Проверяет, содержит ли текст хотя бы один символ выбранного алфавита.
     */
    public function hasAlphabetCharacters(string $text, string $alphabet): bool
    {
        $letters = $this->catalog->alphabet($alphabet);
        $set = array_flip($letters);
        $folded = $this->caseFolder()->toLower($text, $alphabet);

        $length = mb_strlen($folded);
        for ($i = 0; $i < $length; $i++) {
            if (isset($set[mb_substr($folded, $i, 1)])) {
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
            $set    = array_flip($letters);
            $folded = $this->caseFolder()->toLower($text, $code);
            $scores[$code] = 0;

            $length = mb_strlen($folded);
            for ($i = 0; $i < $length; $i++) {
                if (isset($set[mb_substr($folded, $i, 1)])) {
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

    /**
     * Возвращает CaseFolder, создавая fallback-инстанс при необходимости.
     */
    private function caseFolder(): CaseFolder
    {
        return $this->caseFolder ?? new CaseFolder();
    }
}
