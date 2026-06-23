<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Контекст идентификации шифра, передаваемый всем детекторам.
 *
 * Предвычисляет один раз и кэширует общие величины (длина без пробелов,
 * подсчёт букв алфавита, IoC). Все детекторы обращаются к этим данным
 * через геттеры — это снимает дублирование O(text × detectors).
 */
final class IdentificationContext
{
    /** @var array<string, int> Алфавит → количество букв алфавита в тексте. */
    private array $letterCounts = [];

    /** @var array<string, float> Алфавит → IoC по этому алфавиту. */
    private array $iocs = [];

    private ?string $detectedAlphabetCache = null;

    private string $cleanedText;

    private int $cleanedLength;

    /**
     * Создаёт контекст идентификации.
     */
    public function __construct(
        public readonly string $text,
        public readonly ?string $alphabet,
        private readonly LetterFrequencyScorer $scorer,
        private readonly IndexOfCoincidence $ioc,
    ) {
        $cleaned             = preg_replace('/\s+/', '', $this->text);
        $this->cleanedText   = $cleaned ?? $this->text;
        $this->cleanedLength = mb_strlen($this->cleanedText);
    }

    /**
     * Возвращает текст без whitespace.
     */
    public function cleanedText(): string
    {
        return $this->cleanedText;
    }

    /**
     * Возвращает длину текста без whitespace (в символах Unicode).
     */
    public function cleanedLength(): int
    {
        return $this->cleanedLength;
    }

    /**
     * Возвращает наиболее вероятный алфавит текста.
     */
    public function detectedAlphabet(): string
    {
        return $this->detectedAlphabetCache ??= $this->scorer->detectAlphabet($this->text);
    }

    /**
     * Возвращает алфавит для анализа: явно заданный или автоопределённый.
     */
    public function effectiveAlphabet(): string
    {
        return $this->alphabet ?? $this->detectedAlphabet();
    }

    /**
     * Возвращает количество букв заданного алфавита в тексте.
     */
    public function letterCount(string $alphabet): int
    {
        return $this->letterCounts[$alphabet] ??= $this->scorer->countLetters($this->text, $alphabet);
    }

    /**
     * Возвращает IoC текста по заданному алфавиту.
     */
    public function iocFor(string $alphabet): float
    {
        return $this->iocs[$alphabet] ??= $this->ioc->compute($this->text, $alphabet);
    }

    /**
     * Возвращает долю букв заданного алфавита в тексте (без whitespace).
     */
    public function letterRatio(string $alphabet): float
    {
        return $this->cleanedLength > 0
            ? $this->letterCount($alphabet) / $this->cleanedLength
            : 0.0;
    }

    /**
     * Возвращает true, если текст можно надёжно оценивать по статистическим детекторам.
     */
    public function hasReliableSample(string $alphabet): bool
    {
        return $this->letterCount($alphabet) >= LetterFrequencyScorer::MIN_LETTERS_FOR_RELIABLE_SCORING;
    }
}
