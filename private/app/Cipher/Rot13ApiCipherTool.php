<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент ROT13.
 */
final readonly class Rot13ApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента ROT13.
     */
    public function __construct(
        private Rot13CipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'rot13';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $payload): array
    {
        $text = (string) ($payload['text'] ?? '');
        $direction = (string) ($payload['direction'] ?? 'encrypt');

        $errors = [];
        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = trans('ROT13_ERR_DIRECTION');
        }
        if ($text === '') {
            $errors['text'][] = trans('ROT13_ERR_TEXT_REQUIRED');
        }
        if ($text !== '' && !$this->cipher->hasLatinCharacters($text)) {
            $errors['text'][] = trans('ROT13_ERR_TEXT_LATIN');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('ROT13_ERR_INVALID'), ['errors' => $errors]);
        }

        return [
            'ok' => true,
            'result' => $this->cipher->process($text),
        ];
    }
}
