<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\IdentificationContext;
use App\Cipher\IndexOfCoincidence;
use App\Cipher\LetterFrequencyScorer;
use PHPUnit\Framework\TestCase;

/**
 * Базовый класс для тестов детекторов: предоставляет фабрику IdentificationContext.
 */
abstract class DetectorTestCase extends TestCase
{
    protected LetterFrequencyScorer $scorer;

    protected IndexOfCoincidence $ioc;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new LetterFrequencyScorer();
        $this->ioc    = new IndexOfCoincidence();
    }

    /**
     * Создаёт контекст идентификации для тестового текста.
     */
    protected function ctx(string $text, ?string $alphabet = null): IdentificationContext
    {
        return new IdentificationContext($text, $alphabet, $this->scorer, $this->ioc);
    }
}
