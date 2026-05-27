<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра Вернама.
 */
final readonly class VernamApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента Вернама.
     */
    public function __construct(
        private VernamCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'vernam';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $payload): array
    {
        $text = (string) ($payload['text'] ?? '');
        $direction = (string) ($payload['direction'] ?? 'encrypt');
        $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $key = trim((string) ($settings['key'] ?? ''));

        $errors = [];
        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = 'Direction must be encrypt or decrypt.';
        }
        if ($text === '') {
            $errors['text'][] = 'Text is required.';
        }
        if ($key === '') {
            $errors['settings.key'][] = 'Key is required.';
        }

        if ($errors !== []) {
            throw new ValidationFailedException('The given data was invalid.', ['errors' => $errors]);
        }

        return [
            'ok' => true,
            'result' => $this->cipher->process($text, $key, $direction),
            'key' => $key,
        ];
    }
}
