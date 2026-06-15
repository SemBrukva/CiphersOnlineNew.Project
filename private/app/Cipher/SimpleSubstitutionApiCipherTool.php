<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра простой замены.
 */
final readonly class SimpleSubstitutionApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента шифра простой замены.
     */
    public function __construct(
        private SimpleSubstitutionCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'simple-substitution';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $payload): array
    {
        $text      = (string) ($payload['text'] ?? '');
        $direction = (string) ($payload['direction'] ?? 'encrypt');
        $settings  = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $key       = trim((string) ($settings['key'] ?? ''));

        $errors = [];

        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = trans('SIMPLE_SUBSTITUTION_ERR_DIRECTION');
        }

        if ($text === '') {
            $errors['text'][] = trans('SIMPLE_SUBSTITUTION_ERR_TEXT_REQUIRED');
        }

        if ($key === '') {
            $errors['settings.key'][] = trans('SIMPLE_SUBSTITUTION_ERR_KEY_REQUIRED');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('SIMPLE_SUBSTITUTION_ERR_INVALID'), ['errors' => $errors]);
        }

        $alphabet = $this->cipher->detectAlphabetFromKey($key);

        if ($alphabet === null) {
            throw new ValidationFailedException(trans('SIMPLE_SUBSTITUTION_ERR_INVALID'), [
                'errors' => ['settings.key' => [trans('SIMPLE_SUBSTITUTION_ERR_KEY_INVALID')]],
            ]);
        }

        if (!$this->cipher->textContainsAlphabetChars($text, $alphabet)) {
            throw new ValidationFailedException(trans('SIMPLE_SUBSTITUTION_ERR_INVALID'), [
                'errors' => ['text' => [trans('SIMPLE_SUBSTITUTION_ERR_TEXT_ALPHABET')]],
            ]);
        }

        return [
            'ok'       => true,
            'result'   => $this->cipher->process($text, $alphabet, $key, $direction),
            'alphabet' => $alphabet,
        ];
    }
}
