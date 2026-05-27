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
        if ($alphabet === 'auto') {
            $detectedAlphabet = $direction === 'decrypt'
                ? 'en'
                : $this->cipher->detectAlphabet($text);
            $alphabet = $detectedAlphabet;
        }

        if ($direction === 'encrypt' && !$this->cipher->hasAlphabetCharacters($text, $alphabet)) {
            $errors['text'][] = 'Input does not contain symbols from the selected alphabet.';
        }

        if ($errors !== []) {
            throw new ValidationFailedException('The given data was invalid.', ['errors' => $errors]);
        }

        return [
            'ok' => true,
            'result' => $this->cipher->process($text, $alphabet, $direction),
            'detected_alphabet' => $detectedAlphabet,
            'alphabet' => $alphabet,
        ];
    }
}
