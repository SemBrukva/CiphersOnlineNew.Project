<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра Гронсфельда.
 */
final readonly class GronsfeldApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента Гронсфельда.
     */
    public function __construct(
        private GronsfeldCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'gronsfeld';
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
            $errors['direction'][] = trans('GRONSFELD_ERR_DIRECTION');
        }
        if ($text === '') {
            $errors['text'][] = trans('GRONSFELD_ERR_TEXT_REQUIRED');
        }
        if (!$this->cipher->isValidNumericKey($key)) {
            $errors['settings.key'][] = trans('GRONSFELD_ERR_KEY_INVALID');
        }
        if (!in_array($alphabet, array_merge(['auto'], $this->cipher->supportedAlphabetCodes()), true)) {
            $errors['settings.alphabet'][] = trans('GRONSFELD_ERR_ALPHABET_UNSUPPORTED');
        }

        $detectedAlphabet = null;
        if ($alphabet === 'auto') {
            $detectedAlphabet = $this->cipher->detectAlphabet($text);
            $alphabet = $detectedAlphabet;
        }

        if (!$this->cipher->hasAlphabetCharacters($text, $alphabet)) {
            $errors['text'][] = trans('GRONSFELD_ERR_TEXT_ALPHABET');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('GRONSFELD_ERR_INVALID'), ['errors' => $errors]);
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
