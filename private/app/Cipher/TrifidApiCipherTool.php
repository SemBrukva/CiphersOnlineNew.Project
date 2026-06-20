<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра Трифид.
 */
final readonly class TrifidApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента шифра Трифид.
     */
    public function __construct(
        private TrifidCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'trifid';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $payload): array
    {
        $text      = (string) ($payload['text'] ?? '');
        $direction = (string) ($payload['direction'] ?? 'encrypt');
        $settings  = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $alphabet  = mb_strtolower(trim((string) ($settings['alphabet'] ?? 'auto')));
        $key       = trim((string) ($settings['key'] ?? ''));

        $errors = [];

        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = trans('TRIFID_ERR_DIRECTION');
        }

        if ($text === '') {
            $errors['text'][] = trans('TRIFID_ERR_TEXT_REQUIRED');
        }

        if ($key === '') {
            $errors['settings.key'][] = trans('TRIFID_ERR_KEY_REQUIRED');
        }

        if (!in_array($alphabet, array_merge(['auto'], $this->cipher->supportedAlphabetCodes()), true)) {
            $errors['settings.alphabet'][] = trans('TRIFID_ERR_ALPHABET_UNSUPPORTED');
        }

        $detectedAlphabet = null;
        if ($alphabet === 'auto') {
            $detectedAlphabet = $this->cipher->detectAlphabet($text . ' ' . $key);
            $alphabet         = $detectedAlphabet;
        }

        if ($text !== '' && !$this->cipher->hasAlphabetCharacters($text, $alphabet)) {
            $errors['text'][] = trans('TRIFID_ERR_TEXT_NO_LETTERS');
        }

        if ($key !== '' && !$this->cipher->hasAlphabetCharacters($key, $alphabet)) {
            $errors['settings.key'][] = trans('TRIFID_ERR_KEY_NO_LETTERS');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('TRIFID_ERR_INVALID'), ['errors' => $errors]);
        }

        return [
            'ok'                => true,
            'result'            => $this->cipher->process($text, $key, $alphabet, $direction),
            'detected_alphabet' => $detectedAlphabet,
            'alphabet'          => $alphabet,
            'key'               => $key,
        ];
    }
}
