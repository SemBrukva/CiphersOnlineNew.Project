<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент взлома однобайтового XOR-шифра (brute force).
 *
 * Вход — hex-представление зашифрованных байт (типичная подача XOR-шифртекста,
 * т.к. XOR даёт непечатные байты). Перебираются все 256 однобайтовых ключей;
 * каждая расшифровка оценивается долей печатных символов и биграммным/триграммным
 * лог-правдоподобием языка. Возвращается ранжированный список кандидатов.
 */
final readonly class XorBruteForceApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Жёсткий потолок длины входа (в hex-символах). Защита от DoS: перебор —
     * O(256 × bytes), плюс n-gram-скоринг печатных кандидатов.
     */
    public const int MAX_HEX_LENGTH = 20000;

    /** Сколько кандидатов отдаётся клиенту. */
    private const int MAX_CANDIDATES = 10;

    /** Минимальная доля букв+пробелов, при которой считаем n-gram-скор. */
    private const float LETTERS_FOR_NGRAM = 0.6;

    /**
     * Создаёт экземпляр инструмента перебора однобайтового XOR.
     */
    public function __construct(
        private LetterFrequencyScorer $scorer,
        private BigramFrequencyScorer $bigramScorer,
        private TrigramFrequencyScorer $trigramScorer,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'xor-brute-force';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $payload): array
    {
        $text  = (string) ($payload['text'] ?? '');
        $clean = (string) preg_replace('/[\s:,]+/', '', $text);

        $errors = [];
        if ($clean === '') {
            $errors['text'][] = trans('XOR_BRUTE_ERR_TEXT_REQUIRED');
        } elseif (!preg_match('/^[0-9a-fA-F]+$/', $clean) || strlen($clean) % 2 !== 0) {
            $errors['text'][] = trans('XOR_BRUTE_ERR_NOT_HEX');
        } elseif (strlen($clean) > self::MAX_HEX_LENGTH) {
            $errors['text'][] = trans('XOR_BRUTE_ERR_TEXT_TOO_LONG', ['limit' => (string) self::MAX_HEX_LENGTH]);
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('XOR_BRUTE_ERR_INVALID'), ['errors' => $errors]);
        }

        $bytes = (string) hex2bin($clean);
        $n     = strlen($bytes);

        $candidates = [];
        for ($key = 0; $key < 256; $key++) {
            $decoded = $this->xorWithByte($bytes, $key, $n);
            $score   = $this->scoreCandidate($decoded, $n);
            $candidates[] = ['key' => $key, 'text' => $decoded, 'score' => $score];
        }

        usort($candidates, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        $finalists = array_slice($candidates, 0, self::MAX_CANDIDATES);

        $fitness = $this->computeFitness(array_column($finalists, 'score'));

        $results = [];
        foreach ($finalists as $i => $cand) {
            $results[] = [
                'key'     => sprintf('0x%02X', $cand['key']),
                'text'    => $cand['text'],
                'fitness' => $fitness[$i],
            ];
        }

        $best = $finalists[0];

        return [
            'ok'        => true,
            'key'       => sprintf('0x%02X', $best['key']),
            'decrypted' => $best['text'],
            'best_key'  => $best['key'],
            'results'   => $results,
            'reliable'  => $best['score'] >= 1.0,
        ];
    }

    /**
     * XOR всех байт строки с одним ключом-байтом.
     */
    private function xorWithByte(string $bytes, int $key, int $n): string
    {
        $out = '';
        for ($i = 0; $i < $n; $i++) {
            $out .= chr(ord($bytes[$i]) ^ $key);
        }

        return $out;
    }

    /**
     * Оценивает расшифровку. Главный дискриминатор — доля букв и пробелов:
     * естественный текст почти целиком состоит из них, а неверный ключ даёт
     * символьно-цифровой мусор или управляющие байты. Биграммный/триграммный
     * бонус — тайбрейк среди буквенно-насыщенных кандидатов; сам по себе средний
     * биграммный скор ненадёжен (вырожденное «llll» даёт высокий средний ранг).
     */
    private function scoreCandidate(string $decoded, int $n): float
    {
        if ($n === 0) {
            return 0.0;
        }

        $printable   = 0;
        $letterSpace = 0;
        for ($i = 0; $i < $n; $i++) {
            $c = ord($decoded[$i]);
            if ($c === 0x09 || $c === 0x0A || $c === 0x0D || ($c >= 0x20 && $c <= 0x7E)) {
                $printable++;
            }
            if ($c === 0x20 || ($c >= 0x41 && $c <= 0x5A) || ($c >= 0x61 && $c <= 0x7A)) {
                $letterSpace++;
            }
        }
        $printableRatio   = $printable / $n;
        $letterSpaceRatio = $letterSpace / $n;

        // Доля букв+пробелов доминирует; печатность — вспомогательный сигнал.
        $score = 0.35 * $printableRatio + 0.65 * $letterSpaceRatio;

        if ($letterSpaceRatio >= self::LETTERS_FOR_NGRAM && mb_check_encoding($decoded, 'UTF-8')) {
            $alphabet = $this->scorer->detectAlphabet($decoded);
            if ($this->bigramScorer->supports($alphabet) && $this->scorer->countLetters($decoded, $alphabet) >= 4) {
                $bigram = $this->bigramScorer->score($decoded, $alphabet);
                $score += 0.25 * $this->clamp01(0.5 + $bigram / 5.0);
                if ($this->trigramScorer->supports($alphabet)) {
                    $trigram = $this->trigramScorer->score($decoded, $alphabet);
                    $score += 0.25 * $this->clamp01(0.5 + $trigram / 6.0);
                }
            }
        }

        return $score;
    }

    /**
     * Нормализует скоры (выше — лучше) к оценкам пригодности 0..100.
     *
     * @param  float[] $scores
     * @return int[]
     */
    private function computeFitness(array $scores): array
    {
        if ($scores === []) {
            return [];
        }
        $max = max($scores);
        $min = min($scores);
        if ($max - $min < 1e-9) {
            return array_fill(0, count($scores), 100);
        }

        return array_map(
            static fn (float $s): int => (int) round(100 * ($s - $min) / ($max - $min)),
            $scores
        );
    }

    /**
     * Ограничивает значение диапазоном [0.0, 1.0].
     */
    private function clamp01(float $value): float
    {
        return max(0.0, min(1.0, $value));
    }
}
