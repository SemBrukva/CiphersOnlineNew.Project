<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра Хилла.
 */
final readonly class HillApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента шифра Хилла.
     */
    public function __construct(
        private HillCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'hill';
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
        $key = trim((string) ($settings['matrix'] ?? $settings['key'] ?? '3 3; 2 5'));
        $matrix = $this->cipher->parseMatrix($key);

        $errors = [];
        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = trans('HILL_ERR_DIRECTION');
        }
        if ($text === '') {
            $errors['text'][] = trans('HILL_ERR_TEXT_REQUIRED');
        }
        if (!in_array($alphabet, array_merge(['auto'], $this->cipher->supportedAlphabetCodes()), true)) {
            $errors['settings.alphabet'][] = trans('HILL_ERR_ALPHABET_UNSUPPORTED');
        }
        if (!$this->cipher->isSupportedMatrix($matrix)) {
            $errors['settings.key'][] = trans('HILL_ERR_MATRIX_SHAPE', [
                'min' => HillCipherService::MIN_MATRIX_SIZE,
                'max' => HillCipherService::MAX_MATRIX_SIZE,
            ]);
        }

        $detectedAlphabet = null;
        if ($alphabet === 'auto') {
            $detectedAlphabet = $this->cipher->detectAlphabet($text);
            $alphabet = $detectedAlphabet;
        }

        if (!$this->cipher->hasAlphabetCharacters($text, $alphabet)) {
            $errors['text'][] = trans('HILL_ERR_TEXT_ALPHABET');
        }

        if ($this->cipher->isSupportedMatrix($matrix)) {
            $alphabetSize = $this->cipher->alphabetSize($alphabet);
            if (!$this->cipher->isInvertibleMatrix($matrix, $alphabetSize)) {
                $errors['settings.key'][] = trans('HILL_ERR_MATRIX_INVERTIBLE', ['size' => $alphabetSize]);
            }
        }

        if ($direction === 'decrypt' && $this->letterCount($text, $alphabet) % max(1, count($matrix)) !== 0) {
            $errors['text'][] = trans('HILL_ERR_DECRYPT_LENGTH');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('HILL_ERR_INVALID'), ['errors' => $errors]);
        }

        return [
            'ok' => true,
            'result' => $this->cipher->process($text, $alphabet, $matrix, $direction),
            'detected_alphabet' => $detectedAlphabet,
            'alphabet' => $alphabet,
            'matrix' => $matrix,
        ];
    }

    /**
     * Подсчитывает буквы выбранного алфавита в тексте.
     */
    private function letterCount(string $text, string $alphabet): int
    {
        $catalog = new AlphabetCatalog();
        $letters = array_flip($catalog->alphabet($alphabet));
        $count = 0;
        $length = mb_strlen($text);

        for ($i = 0; $i < $length; $i++) {
            if (isset($letters[mb_strtolower(mb_substr($text, $i, 1))])) {
                $count++;
            }
        }

        return $count;
    }
}
