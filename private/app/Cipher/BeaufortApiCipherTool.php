<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра Бофора.
 */
final readonly class BeaufortApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента Бофора.
     */
    public function __construct(
        private BeaufortCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'beaufort';
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
            $errors['direction'][] = trans('BEAUFORT_ERR_DIRECTION');
        }
        if ($text === '') {
            $errors['text'][] = trans('BEAUFORT_ERR_TEXT_REQUIRED');
        }
        if ($key === '') {
            $errors['settings.key'][] = trans('BEAUFORT_ERR_KEY_REQUIRED');
        }
        if (!in_array($alphabet, array_merge(['auto'], $this->cipher->supportedAlphabetCodes()), true)) {
            $errors['settings.alphabet'][] = trans('BEAUFORT_ERR_ALPHABET_UNSUPPORTED');
        }

        $detectedAlphabet = null;
        if ($alphabet === 'auto') {
            $detectedAlphabet = $this->cipher->detectAlphabet($text . ' ' . $key);
            $alphabet = $detectedAlphabet;
        }

        if (!$this->cipher->hasAlphabetCharacters($text, $alphabet)) {
            $errors['text'][] = trans('BEAUFORT_ERR_TEXT_ALPHABET');
        }
        if (!$this->cipher->hasAlphabetCharacters($key, $alphabet)) {
            $errors['settings.key'][] = trans('BEAUFORT_ERR_KEY_ALPHABET');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('BEAUFORT_ERR_INVALID'), ['errors' => $errors]);
        }

        return [
            'ok' => true,
            'result' => $this->cipher->process($text, $key, $alphabet),
            'detected_alphabet' => $detectedAlphabet,
            'alphabet' => $alphabet,
            'key' => $key,
        ];
    }
}
