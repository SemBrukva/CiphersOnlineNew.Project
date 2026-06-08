<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра квадрата Полибия.
 */
final readonly class PolybiusSquareApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента квадрата Полибия.
     */
    public function __construct(
        private PolybiusSquareCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'polybius-square';
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
        $delimiter = mb_strtolower(trim((string) ($settings['delimiter'] ?? 'space')));

        $errors = [];
        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = trans('POLYBIUS_ERR_DIRECTION');
        }
        if ($text === '') {
            $errors['text'][] = trans('POLYBIUS_ERR_TEXT_REQUIRED');
        }
        if (!in_array($alphabet, array_merge(['auto'], $this->cipher->supportedAlphabetCodes()), true)) {
            $errors['settings.alphabet'][] = trans('POLYBIUS_ERR_ALPHABET_UNSUPPORTED');
        }
        if (!in_array($delimiter, ['dash', 'space', 'comma', 'slash', 'dot'], true)) {
            $errors['settings.delimiter'][] = trans('POLYBIUS_ERR_DELIMITER');
        }

        $detectedAlphabet = null;
        if ($alphabet === 'auto') {
            if ($direction === 'decrypt') {
                $pageLocale = mb_strtolower(trim((string) ($payload['locale'] ?? 'en')));
                $supportedCodes = $this->cipher->supportedAlphabetCodes();
                $detectedAlphabet = in_array($pageLocale, $supportedCodes, true) ? $pageLocale : 'en';
            } else {
                $detectedAlphabet = $this->cipher->detectAlphabet($text);
            }
            $alphabet = $detectedAlphabet;
        }

        if ($direction === 'encrypt' && !$this->cipher->hasAlphabetCharacters($text, $alphabet)) {
            $errors['text'][] = trans('POLYBIUS_ERR_TEXT_ALPHABET');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('POLYBIUS_ERR_INVALID'), ['errors' => $errors]);
        }

        return [
            'ok' => true,
            'result' => $this->cipher->process($text, $alphabet, $direction, $delimiter),
            'detected_alphabet' => $detectedAlphabet,
            'alphabet' => $alphabet,
            'delimiter' => $delimiter,
        ];
    }
}
