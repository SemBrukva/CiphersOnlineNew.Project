<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент ROT47.
 */
final readonly class Rot47ApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента ROT47.
     */
    public function __construct(
        private Rot47CipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'rot47';
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
            $errors['direction'][] = trans('ROT47_ERR_DIRECTION');
        }
        if ($text === '') {
            $errors['text'][] = trans('ROT47_ERR_TEXT_REQUIRED');
        }
        if ($text !== '' && !$this->cipher->hasPrintableAscii($text)) {
            $errors['text'][] = trans('ROT47_ERR_TEXT_ASCII');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('ROT47_ERR_INVALID'), ['errors' => $errors]);
        }

        return [
            'ok' => true,
            'result' => $this->cipher->process($text),
        ];
    }
}
