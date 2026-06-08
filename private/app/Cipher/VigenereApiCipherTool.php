<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра Виженера.
 */
final readonly class VigenereApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента Виженера.
     */
    public function __construct(
        private VigenereCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'vigenere';
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
        $key = trim((string) ($settings['key'] ?? ''));

        $errors = [];
        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = trans('VIGENERE_ERR_DIRECTION');
        }
        if ($text === '') {
            $errors['text'][] = trans('VIGENERE_ERR_TEXT_REQUIRED');
        }
        if ($key === '') {
            $errors['settings.key'][] = trans('VIGENERE_ERR_KEY_REQUIRED');
        }
        if (mb_strlen($text) < mb_strlen($key)) {
            $errors['settings.key'][] = trans('VIGENERE_ERR_KEY_TOO_LONG');
        }
        if (!in_array($alphabet, array_merge(['auto'], $this->cipher->supportedAlphabetCodes()), true)) {
            $errors['settings.alphabet'][] = trans('VIGENERE_ERR_ALPHABET_UNSUPPORTED');
        }

        $detectedAlphabet = null;
        if ($alphabet === 'auto') {
            $detectedAlphabet = $this->cipher->detectAlphabet($text . ' ' . $key);
            $alphabet = $detectedAlphabet;
        }

        if (!$this->cipher->hasAlphabetCharacters($text, $alphabet)) {
            $errors['text'][] = trans('VIGENERE_ERR_TEXT_ALPHABET');
        }
        if (!$this->cipher->hasAlphabetCharacters($key, $alphabet)) {
            $errors['settings.key'][] = trans('VIGENERE_ERR_KEY_ALPHABET');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('VIGENERE_ERR_INVALID'), ['errors' => $errors]);
        }

        return [
            'ok' => true,
            'result' => $this->cipher->process($text, $key, $alphabet, $direction),
            'detected_alphabet' => $detectedAlphabet,
            'alphabet' => $alphabet,
            'key' => $key,
        ];
    }
}
