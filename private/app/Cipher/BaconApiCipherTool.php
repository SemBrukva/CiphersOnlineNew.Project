<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра Бэкона.
 */
final readonly class BaconApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента Бэкона.
     */
    public function __construct(
        private BaconCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'bacon';
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
        $coverText = (string) ($settings['cover_text'] ?? '');
        $hasStego = $direction === 'encrypt' && $coverText !== '';

        $errors = [];
        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = trans('BACON_ERR_DIRECTION');
        }
        if ($text === '') {
            $errors['text'][] = trans('BACON_ERR_TEXT_REQUIRED');
        }
        if (!in_array($alphabet, array_merge(['auto'], $this->cipher->supportedAlphabetCodes()), true)) {
            $errors['settings.alphabet'][] = trans('BACON_ERR_ALPHABET_UNSUPPORTED');
        }

        $detectedAlphabet = null;
        if ($alphabet === 'auto') {
            $detectedAlphabet = $direction === 'decrypt'
                ? 'en'
                : $this->cipher->detectAlphabet($text);
            $alphabet = $detectedAlphabet;
        }

        if ($direction === 'encrypt' && !$this->cipher->hasAlphabetCharacters($text, $alphabet)) {
            $errors['text'][] = trans('BACON_ERR_TEXT_ALPHABET');
        }

        if ($hasStego && $errors === []) {
            $available = $this->cipher->countLetters($coverText);
            // +10 букв под 10-битный заголовок длины
            $needed = $this->cipher->encodedBitCount($text, $alphabet) + 10;
            if ($available < $needed) {
                $errors['settings.cover_text'][] = trans('BACON_ERR_COVER_TOO_SHORT', [
                    'needed'    => $needed,
                    'available' => $available,
                ]);
            }
        }

        if ($errors !== []) {
            throw new ValidationFailedException('The given data was invalid.', ['errors' => $errors]);
        }

        if ($direction === 'encrypt') {
            $result = $hasStego
                ? $this->cipher->steganographyEncrypt($text, $coverText, $alphabet)
                : $this->cipher->process($text, $alphabet, 'encrypt');
        } else {
            $result = $this->cipher->isStegoText($text)
                ? $this->cipher->steganographyDecrypt($text, $alphabet)
                : $this->cipher->process($text, $alphabet, 'decrypt');
        }

        return [
            'ok' => true,
            'result' => $result,
            'detected_alphabet' => $detectedAlphabet,
            'alphabet' => $alphabet,
        ];
    }
}
