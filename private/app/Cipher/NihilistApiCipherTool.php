<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра нигилистов (Nihilist substitution).
 */
final readonly class NihilistApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента шифра нигилистов.
     */
    public function __construct(
        private NihilistCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'nihilist';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $payload): array
    {
        $text        = (string) ($payload['text'] ?? '');
        $direction   = (string) ($payload['direction'] ?? 'encrypt');
        $settings    = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $alphabet    = mb_strtolower(trim((string) ($settings['alphabet'] ?? 'auto')));
        $squareKey   = trim((string) ($settings['key'] ?? ''));
        $additiveKey = trim((string) ($settings['additive_key'] ?? ''));

        $errors = [];

        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = trans('NIHILIST_ERR_DIRECTION');
        }

        if ($text === '') {
            $errors['text'][] = trans('NIHILIST_ERR_TEXT_REQUIRED');
        }

        if ($squareKey === '') {
            $errors['settings.key'][] = trans('NIHILIST_ERR_SQUARE_KEY_REQUIRED');
        }

        if ($additiveKey === '') {
            $errors['settings.additive_key'][] = trans('NIHILIST_ERR_ADDITIVE_KEY_REQUIRED');
        }

        if (!in_array($alphabet, array_merge(['auto'], $this->cipher->supportedAlphabetCodes()), true)) {
            $errors['settings.alphabet'][] = trans('NIHILIST_ERR_ALPHABET_UNSUPPORTED');
        }

        $detectedAlphabet = null;
        if ($alphabet === 'auto') {
            // При расшифровке шифротекст состоит из чисел, поэтому алфавит
            // определяется по ключам, а не по самому тексту.
            $sample           = $direction === 'decrypt'
                ? $squareKey . ' ' . $additiveKey
                : $text . ' ' . $squareKey;
            $detectedAlphabet = $this->cipher->detectAlphabet($sample);
            $alphabet         = $detectedAlphabet;
        }

        if ($errors === [] && $text !== '') {
            if ($direction === 'decrypt') {
                if (!$this->cipher->hasNumberGroups($text)) {
                    $errors['text'][] = trans('NIHILIST_ERR_TEXT_NO_NUMBERS');
                }
            } elseif (!$this->cipher->hasSquareLetters($text, $alphabet)) {
                $errors['text'][] = trans('NIHILIST_ERR_TEXT_NO_LETTERS');
            }
        }

        if ($squareKey !== '' && !$this->cipher->hasSquareLetters($squareKey, $alphabet)) {
            $errors['settings.key'][] = trans('NIHILIST_ERR_SQUARE_KEY_NO_LETTERS');
        }

        if ($additiveKey !== '' && !$this->cipher->hasSquareLetters($additiveKey, $alphabet)) {
            $errors['settings.additive_key'][] = trans('NIHILIST_ERR_ADDITIVE_KEY_NO_LETTERS');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('NIHILIST_ERR_INVALID'), ['errors' => $errors]);
        }

        $detailed = $this->cipher->processDetailed($text, $squareKey, $additiveKey, $alphabet, $direction);

        return [
            'ok'                => true,
            'result'            => $detailed['result'],
            'square'            => $detailed['square'],
            'square_size'       => $detailed['size'],
            'steps'             => $detailed['steps'],
            'direction'         => $direction,
            'detected_alphabet' => $detectedAlphabet,
            'alphabet'          => $alphabet,
            'key'               => $squareKey,
        ];
    }
}
