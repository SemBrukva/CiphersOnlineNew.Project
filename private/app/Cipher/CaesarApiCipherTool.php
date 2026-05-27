<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра Цезаря.
 */
final readonly class CaesarApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента Цезаря.
     */
    public function __construct(
        private CaesarCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'caesar';
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
        $shift = (int) ($settings['shift'] ?? 0);

        $errors = [];
        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = 'Direction must be encrypt or decrypt.';
        }
        if ($text === '') {
            $errors['text'][] = 'Text is required.';
        }
        if (!in_array($alphabet, array_merge(['auto'], $this->cipher->supportedAlphabetCodes()), true)) {
            $errors['settings.alphabet'][] = 'Unsupported alphabet.';
        }

        $detectedAlphabet = null;
        $usedAutoAlphabet = false;
        if ($alphabet === 'auto') {
            $detectedAlphabet = $this->cipher->detectAlphabet($text);
            $alphabet = $detectedAlphabet;
            $usedAutoAlphabet = true;
        }

        if (!$this->cipher->hasAlphabetCharacters($text, $alphabet)) {
            $errors['text'][] = 'Input does not contain symbols from the selected alphabet.';
        }

        $maxShift = $this->cipher->maxShiftForAlphabet($alphabet);
        if ($shift < 0 || $shift > $maxShift) {
            if ($usedAutoAlphabet) {
                $shift = max(0, min($shift, $maxShift));
            } else {
                $errors['settings.shift'][] = 'Shift must be in range 0-' . $maxShift . '.';
            }
        }

        if ($errors !== []) {
            throw new ValidationFailedException('The given data was invalid.', ['errors' => $errors]);
        }

        return [
            'ok' => true,
            'result' => $this->cipher->process($text, $alphabet, $shift, $direction),
            'detected_alphabet' => $detectedAlphabet,
            'alphabet' => $alphabet,
            'shift' => $shift,
        ];
    }
}

