<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент «умного» авто-солвера.
 *
 * Единое поле ввода → {@see SolverService} определяет тип, прогоняет авто-взлом
 * и возвращает расшифровки, отранжированные по читаемости. Верхний элемент —
 * «вероятный ответ». Вторым уровнем отдаётся список типов-кандидатов (той же
 * формы, что у cipher-identifier) для UI-блока «прочие варианты».
 */
final readonly class SolverApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Максимальная длина входного текста (символов Unicode).
     */
    public const int MAX_TEXT_LENGTH = 3000;

    /**
     * Создаёт экземпляр API-инструмента солвера.
     */
    public function __construct(
        private SolverService $solver,
        private ApiCipherToolExecutorInterface $executor,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'cipher-solver';
    }

    /**
     * {@inheritDoc}
     *
     * @return array{
     *   ok: true,
     *   best: array<string, mixed>|null,
     *   answers: array<int, array<string, mixed>>,
     *   type_candidates: array<int, array<string, mixed>>,
     *   detected_alphabet: string|null
     * }
     */
    public function execute(array $payload): array
    {
        $text     = trim((string) ($payload['text'] ?? ''));
        $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $alphabet = mb_strtolower(trim((string) ($settings['alphabet'] ?? 'auto')));

        $errors = [];
        if ($text === '') {
            $errors['text'][] = trans('CIPHER_IDENTIFIER_ERR_TEXT_REQUIRED');
        } elseif (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            $errors['text'][] = trans('CIPHER_IDENTIFIER_ERR_TEXT_TOO_LONG');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('CIPHER_IDENTIFIER_ERR_TEXT_REQUIRED'), ['errors' => $errors]);
        }

        $alphabetOrNull = ($alphabet === 'auto') ? null : $alphabet;
        $solution       = $this->solver->solve($text, $alphabetOrNull, $this->executor);

        /** @var SolverResult[] $answers */
        $answers = $solution['answers'];
        /** @var SolverResult|null $best */
        $best = $solution['best'];
        /** @var CipherDetection[] $detections */
        $detections = $solution['detections'];

        $typeCandidates = array_map(static fn (CipherDetection $d): array => [
            'tool_slug'          => $d->toolSlug,
            'cipher_key'         => $d->cipherKey,
            'confidence'         => round($d->confidence, 4),
            'confidence_pct'     => (int) round($d->confidence * 100),
            'evidence_keys'      => $d->evidenceKeys,
            'brute_force_action' => $d->bruteForceAction,
            'detected_alphabet'  => $d->detectedAlphabet,
            'hints'              => $d->hints,
        ], $detections);

        return [
            'ok'                => true,
            'best'              => $best?->toArray(),
            'answers'           => array_map(static fn (SolverResult $r): array => $r->toArray(), $answers),
            'type_candidates'   => $typeCandidates,
            'detected_alphabet' => $best->detectedAlphabet ?? ($detections[0]->detectedAlphabet ?? null),
        ];
    }
}
