<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент идентификации шифра/кодировки по произвольному тексту.
 *
 * Возвращает ранжированный список кандидатов с confidence.
 * Если лидер уверен и имеет brute-force — запускает его автоматически.
 */
final readonly class CipherIdentifierApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Максимальная длина входного текста.
     */
    public const int MAX_TEXT_LENGTH = 3000;

    /**
     * Создаёт экземпляр API-инструмента.
     */
    public function __construct(
        private CipherIdentifierService $identifier,
        private ApiCipherToolExecutorInterface $apiRegistry,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'cipher-identifier';
    }

    /**
     * {@inheritDoc}
     *
     * @return array{
     *   ok: true,
     *   candidates: array<int, array<string, mixed>>,
     *   auto_action: string|null,
     *   auto_result: array<string, mixed>|null,
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
        $detections     = $this->identifier->identify($text, $alphabetOrNull);

        $candidates = array_map(static fn (CipherDetection $d): array => [
            'tool_slug'         => $d->toolSlug,
            'cipher_key'        => $d->cipherKey,
            'confidence'        => round($d->confidence, 4),
            'confidence_pct'    => (int) round($d->confidence * 100),
            'evidence_keys'     => $d->evidenceKeys,
            'brute_force_action' => $d->bruteForceAction,
            'detected_alphabet' => $d->detectedAlphabet,
            'hints'             => $d->hints,
        ], $detections);

        $leader       = $detections[0] ?? null;
        $second       = $detections[1] ?? null;
        $autoAction   = null;
        $autoResult   = null;
        $detectedAlph = $leader?->detectedAlphabet;

        if (
            $leader !== null
            && $leader->confidence >= CipherIdentifierService::AUTO_THRESHOLD
            && $leader->bruteForceAction !== null
            && ($second === null || ($leader->confidence - $second->confidence) >= CipherIdentifierService::AUTO_GAP)
        ) {
            $autoAction = $leader->bruteForceAction;
            try {
                $autoResult = $this->apiRegistry->execute($leader->bruteForceAction, [
                    'text'     => $text,
                    'settings' => ['alphabet' => $leader->detectedAlphabet ?? ($alphabetOrNull ?? 'auto')],
                ]);
            } catch (\Throwable) {
                $autoResult = null;
            }
        }

        return [
            'ok'                => true,
            'candidates'        => $candidates,
            'auto_action'       => $autoAction,
            'auto_result'       => $autoResult,
            'detected_alphabet' => $detectedAlph,
        ];
    }
}
