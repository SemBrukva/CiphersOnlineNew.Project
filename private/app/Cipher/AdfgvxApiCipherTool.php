<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра ADFGVX.
 */
final readonly class AdfgvxApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента шифра ADFGVX.
     */
    public function __construct(
        private AdfgvxCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'adfgvx';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $payload): array
    {
        $text             = (string) ($payload['text'] ?? '');
        $direction        = (string) ($payload['direction'] ?? 'encrypt');
        $settings         = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $alphabet         = mb_strtolower(trim((string) ($settings['alphabet'] ?? 'auto')));
        $squareKey        = trim((string) ($settings['key'] ?? ''));
        $transpositionKey = trim((string) ($settings['transposition_key'] ?? ''));

        $errors = [];

        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = trans('ADFGVX_ERR_DIRECTION');
        }

        if ($text === '') {
            $errors['text'][] = trans('ADFGVX_ERR_TEXT_REQUIRED');
        }

        if ($squareKey === '') {
            $errors['settings.key'][] = trans('ADFGVX_ERR_SQUARE_KEY_REQUIRED');
        }

        if ($transpositionKey === '') {
            $errors['settings.transposition_key'][] = trans('ADFGVX_ERR_TRANSPOSITION_KEY_REQUIRED');
        }

        if (!in_array($alphabet, array_merge(['auto'], $this->cipher->supportedAlphabetCodes()), true)) {
            $errors['settings.alphabet'][] = trans('ADFGVX_ERR_ALPHABET_UNSUPPORTED');
        }

        $detectedAlphabet = null;
        if ($alphabet === 'auto') {
            // Для расшифровки текст состоит из меток ADFGVX (латиница), поэтому
            // алфавит определяется по ключу квадрата, а не по шифротексту.
            $sample           = $direction === 'decrypt' ? $squareKey : $text . ' ' . $squareKey;
            $detectedAlphabet = $this->cipher->detectAlphabet($sample);
            $alphabet         = $detectedAlphabet;
        }

        if ($errors === [] && $text !== '') {
            if ($direction === 'decrypt') {
                if (!$this->cipher->hasLabels($text)) {
                    $errors['text'][] = trans('ADFGVX_ERR_TEXT_NO_LABELS');
                }
            } elseif (!$this->cipher->hasSquareCharacters($text, $alphabet)) {
                $errors['text'][] = trans('ADFGVX_ERR_TEXT_NO_SYMBOLS');
            }
        }

        if ($squareKey !== '' && !$this->cipher->hasSquareCharacters($squareKey, $alphabet)) {
            $errors['settings.key'][] = trans('ADFGVX_ERR_SQUARE_KEY_NO_LETTERS');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('ADFGVX_ERR_INVALID'), ['errors' => $errors]);
        }

        return [
            'ok'                => true,
            'result'            => $this->cipher->process($text, $squareKey, $transpositionKey, $alphabet, $direction),
            'detected_alphabet' => $detectedAlphabet,
            'alphabet'          => $alphabet,
            'key'               => $squareKey,
        ];
    }
}
