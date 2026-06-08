<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра Атбаш.
 */
final readonly class AtbashApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента Атбаша.
     */
    public function __construct(
        private AtbashCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'atbash';
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

        $errors = [];
        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = trans('ATBASH_ERR_DIRECTION');
        }
        if ($text === '') {
            $errors['text'][] = trans('ATBASH_ERR_TEXT_REQUIRED');
        }
        if (!in_array($alphabet, array_merge(['auto'], $this->cipher->supportedAlphabetCodes()), true)) {
            $errors['settings.alphabet'][] = trans('ATBASH_ERR_ALPHABET_UNSUPPORTED');
        }

        $detectedAlphabet = null;
        if ($alphabet === 'auto') {
            $detectedAlphabet = $this->cipher->detectAlphabet($text);
            $alphabet = $detectedAlphabet;
        }

        if (!$this->cipher->hasAlphabetCharacters($text, $alphabet)) {
            $errors['text'][] = trans('ATBASH_ERR_TEXT_ALPHABET');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('ATBASH_ERR_INVALID'), ['errors' => $errors]);
        }

        return [
            'ok' => true,
            'result' => $this->cipher->process($text, $alphabet),
            'detected_alphabet' => $detectedAlphabet,
            'alphabet' => $alphabet,
        ];
    }
}
