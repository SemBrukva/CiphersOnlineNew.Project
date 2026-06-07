<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент аффинного шифра.
 */
final readonly class AffineApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента аффинного шифра.
     */
    public function __construct(
        private AffineCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'affine';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $payload): array
    {
        $text = (string) ($payload['text'] ?? '');
        $direction = (string) ($payload['direction'] ?? 'encrypt');
        $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $alphabet = mb_strtolower(trim((string) ($settings['alphabet'] ?? 'auto')));
        $multiplier = (int) ($settings['a'] ?? $settings['multiplier'] ?? $settings['key'] ?? 5);
        $shift = (int) ($settings['b'] ?? $settings['shift'] ?? 8);

        $errors = [];
        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = trans('AFFINE_ERR_DIRECTION');
        }
        if ($text === '') {
            $errors['text'][] = trans('AFFINE_ERR_TEXT_REQUIRED');
        }
        if (!in_array($alphabet, array_merge(['auto'], $this->cipher->supportedAlphabetCodes()), true)) {
            $errors['settings.alphabet'][] = trans('AFFINE_ERR_ALPHABET_UNSUPPORTED');
        }

        $detectedAlphabet = null;
        if ($alphabet === 'auto') {
            $detectedAlphabet = $this->cipher->detectAlphabet($text);
            $alphabet = $detectedAlphabet;
        }

        if (!$this->cipher->hasAlphabetCharacters($text, $alphabet)) {
            $errors['text'][] = trans('AFFINE_ERR_TEXT_ALPHABET');
        }

        $alphabetSize = $this->cipher->alphabetSize($alphabet);
        if (!$this->cipher->isValidMultiplier($multiplier, $alphabet)) {
            $errors['settings.key'][] = trans('AFFINE_ERR_MULTIPLIER', ['size' => $alphabetSize]);
        }
        if ($shift < 0 || $shift >= $alphabetSize) {
            $errors['settings.shift'][] = trans('AFFINE_ERR_SHIFT', ['max' => $alphabetSize - 1]);
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('AFFINE_ERR_INVALID'), ['errors' => $errors]);
        }

        return [
            'ok' => true,
            'result' => $this->cipher->process($text, $alphabet, $multiplier, $shift, $direction),
            'detected_alphabet' => $detectedAlphabet,
            'alphabet' => $alphabet,
            'a' => $multiplier,
            'b' => $shift,
        ];
    }
}
