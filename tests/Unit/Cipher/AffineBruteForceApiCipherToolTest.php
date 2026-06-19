<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cache\NullCache;
use App\Cipher\AffineBruteForceApiCipherTool;
use App\Cipher\AffineCipherService;
use App\Cipher\AlphabetCatalog;
use App\Cipher\BigramFrequencyScorer;
use App\Cipher\LetterFrequencyScorer;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента перебора всех ключей аффинного шифра.
 */
final class AffineBruteForceApiCipherToolTest extends TestCase
{
    private AffineCipherService $cipher;
    private AffineBruteForceApiCipherTool $tool;

    protected function setUp(): void
    {
        $catalog      = new AlphabetCatalog();
        $this->cipher = new AffineCipherService($catalog);
        $this->tool   = new AffineBruteForceApiCipherTool(
            $this->cipher,
            new LetterFrequencyScorer(),
            $catalog,
            new BigramFrequencyScorer(),
            new NullCache()
        );
    }

    /**
     * Проверяет, что action() возвращает 'affine-brute-force'.
     */
    public function testActionReturnsAffineBruteForce(): void
    {
        self::assertSame('affine-brute-force', $this->tool->action());
    }

    /**
     * Находит ключ (a=5, b=8) в каноническом примере «AFFINE CIPHER».
     */
    public function testFindsCanonicalEnglishKey(): void
    {
        $result = $this->tool->execute([
            'text'     => 'IHHWVC SWFRCP',
            'settings' => ['alphabet' => 'en'],
        ]);

        self::assertTrue($result['ok']);
        self::assertSame(5, $result['best_multiplier']);
        self::assertSame(8, $result['best_shift']);

        $bestRow = $this->findRow($result['results'], 5, 8);
        self::assertSame('AFFINE CIPHER', $bestRow['text']);
    }

    /**
     * Находит ключ (a=7, b=3) в более длинном фрагменте.
     */
    public function testFindsKeyOnLongerEnglishText(): void
    {
        $plain     = 'The quick brown fox jumps over the lazy dog and dreams of a hearty breakfast under the warm sun.';
        $encrypted = $this->cipher->process($plain, 'en', 7, 3, 'encrypt');

        $result = $this->tool->execute([
            'text'     => $encrypted,
            'settings' => ['alphabet' => 'en'],
        ]);

        self::assertSame(7, $result['best_multiplier']);
        self::assertSame(3, $result['best_shift']);
        $bestRow = $this->findRow($result['results'], 7, 3);
        self::assertSame($plain, $bestRow['text']);
    }

    /**
     * Регрессия: короткий русский текст (≈65 букв) и ключ (a=5, b=8).
     *
     * До перехода на биграммный скор по всем парам χ² на таком корпусе настолько
     * шумен, что правильный ключ оказывался ~55-м из 660 и отрезался top-K
     * cutoff'ом. Биграммы по всему пространству ключей возвращают его на #1
     * с явным отрывом.
     */
    public function testCracksShortRussianText(): void
    {
        $plain     = 'Шифруйте и расшифровывайте текст аффинным шифром с двумя числовыми ключами';
        $encrypted = $this->cipher->process($plain, 'ru', 5, 8, 'encrypt');

        $result = $this->tool->execute([
            'text'     => $encrypted,
            'settings' => ['alphabet' => 'ru'],
        ]);

        self::assertSame(5, $result['best_multiplier']);
        self::assertSame(8, $result['best_shift']);
        self::assertSame($plain, $result['results'][0]['text']);
    }

    /**
     * Находит русский ключ (a=8, b=10) в публицистическом тексте.
     *
     * Множитель должен быть взаимно прост с m=33; 8 удовлетворяет условию,
     * а 3 или 11 — нет, поэтому здесь используем 8.
     */
    public function testFindsRussianKey(): void
    {
        $plain     = 'Шифры подстановки исторически использовались для защиты дипломатической переписки, военных приказов и тайных дневников.';
        $encrypted = $this->cipher->process($plain, 'ru', 8, 10, 'encrypt');

        $result = $this->tool->execute([
            'text'     => $encrypted,
            'settings' => ['alphabet' => 'ru'],
        ]);

        self::assertSame(8, $result['best_multiplier']);
        self::assertSame(10, $result['best_shift']);
        $bestRow = $this->findRow($result['results'], 8, 10);
        self::assertSame($plain, $bestRow['text']);
    }

    /**
     * Автоопределение алфавита при settings.alphabet = 'auto'.
     */
    public function testAutoDetectsRussianAlphabet(): void
    {
        $plain     = 'Шифры подстановки исторически использовались для защиты дипломатической переписки, военных приказов и тайных дневников.';
        $encrypted = $this->cipher->process($plain, 'ru', 5, 7, 'encrypt');

        $result = $this->tool->execute([
            'text'     => $encrypted,
            'settings' => ['alphabet' => 'auto'],
        ]);

        self::assertSame('ru', $result['alphabet']);
        self::assertSame('ru', $result['detected_alphabet']);
        self::assertSame(5, $result['best_multiplier']);
        self::assertSame(7, $result['best_shift']);
    }

    /**
     * Возвращает только top-N кандидатов, а не всё пространство ключей.
     * UX-аналог vigenere-cracker: подробно показываем только победителей.
     */
    public function testLimitsResultsToTopN(): void
    {
        $result = $this->tool->execute([
            'text'     => 'HELLO WORLD',
            'settings' => ['alphabet' => 'en'],
        ]);

        self::assertLessThanOrEqual(10, count($result['results']));
        self::assertGreaterThan(0, count($result['results']));
    }

    /**
     * Лучший кандидат всегда лежит в results[0] и совпадает с best_multiplier/best_shift.
     * Это контракт фронтенда: первая строка считается активной по умолчанию.
     */
    public function testBestCandidateIsAtIndexZero(): void
    {
        $plain     = 'The quick brown fox jumps over the lazy dog and dreams of a hearty breakfast under the warm sun.';
        $encrypted = $this->cipher->process($plain, 'en', 5, 8, 'encrypt');

        $result = $this->tool->execute([
            'text'     => $encrypted,
            'settings' => ['alphabet' => 'en'],
        ]);

        $first = $result['results'][0];
        self::assertSame($result['best_multiplier'], $first['multiplier']);
        self::assertSame($result['best_shift'], $first['shift']);
        self::assertSame($plain, $first['text']);
    }

    /**
     * Возвращает поля key/decrypted/fitness на верхнем уровне — аналог vigenere-cracker.
     */
    public function testReturnsTopLevelKeyAndDecrypted(): void
    {
        $plain     = 'The quick brown fox jumps over the lazy dog and dreams of a hearty breakfast under the warm sun.';
        $encrypted = $this->cipher->process($plain, 'en', 5, 8, 'encrypt');

        $result = $this->tool->execute([
            'text'     => $encrypted,
            'settings' => ['alphabet' => 'en'],
        ]);

        self::assertSame('a=5, b=8', $result['key']);
        self::assertSame($plain, $result['decrypted']);
        self::assertIsInt($result['fitness']);
    }

    /**
     * Короткий текст помечается как ненадёжный, но запрос не падает.
     */
    public function testShortTextReturnsUnreliableFlag(): void
    {
        $result = $this->tool->execute([
            'text'     => 'AB',
            'settings' => ['alphabet' => 'en'],
        ]);

        self::assertTrue($result['ok']);
        self::assertFalse($result['reliable']);
    }

    /**
     * Текст с количеством букв ≥ порога надёжности помечается как надёжный.
     */
    public function testReliableFlagSetForLongerText(): void
    {
        $plain     = 'The quick brown fox jumps over the lazy dog and dreams of a hearty breakfast under the warm sun.';
        $encrypted = $this->cipher->process($plain, 'en', 5, 8, 'encrypt');

        $result = $this->tool->execute([
            'text'     => $encrypted,
            'settings' => ['alphabet' => 'en'],
        ]);

        self::assertTrue($result['reliable']);
    }

    /**
     * Текст длиннее MAX_TEXT_LENGTH отвергается на валидации.
     */
    public function testRejectsTextLongerThanLimit(): void
    {
        $limit   = AffineBruteForceApiCipherTool::MAX_TEXT_LENGTH;
        $tooLong = str_repeat('a', $limit + 1);

        try {
            $this->tool->execute([
                'text'     => $tooLong,
                'settings' => ['alphabet' => 'en'],
            ]);
            self::fail('Expected ValidationFailedException was not thrown.');
        } catch (ValidationFailedException $e) {
            $message = (string) ($e->details()['errors']['text'][0] ?? '');
            self::assertStringContainsString((string) $limit, $message);
            self::assertStringNotContainsString(':limit', $message);
        }
    }

    /**
     * Пустой текст отвергается на валидации.
     */
    public function testRejectsEmptyText(): void
    {
        $this->expectException(ValidationFailedException::class);
        $this->tool->execute(['text' => '', 'settings' => ['alphabet' => 'en']]);
    }

    /**
     * Сохраняет регистр и пунктуацию в результате — fast-path не должен сломать
     * UTF-8 обработку non-letter символов.
     */
    public function testPreservesCaseAndPunctuation(): void
    {
        $plain     = 'Hello, World! The cipher is fun.';
        $encrypted = $this->cipher->process($plain, 'en', 5, 8, 'encrypt');

        $result  = $this->tool->execute([
            'text'     => $encrypted,
            'settings' => ['alphabet' => 'en'],
        ]);
        $bestRow = $this->findRow($result['results'], 5, 8);

        self::assertSame($plain, $bestRow['text']);
    }

    /**
     * Возвращает строку с заданной парой (a, b) из results.
     *
     * @param  array<int, array{multiplier: int, shift: int, text: string, fitness: int}> $results
     * @return array{multiplier: int, shift: int, text: string, fitness: int}
     */
    private function findRow(array $results, int $a, int $b): array
    {
        foreach ($results as $row) {
            if ($row['multiplier'] === $a && $row['shift'] === $b) {
                return $row;
            }
        }
        self::fail("Row (a={$a}, b={$b}) not found in results.");
    }
}
