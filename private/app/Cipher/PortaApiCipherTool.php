<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра Porta.
 */
final readonly class PortaApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента Porta.
     */
    public function __construct(
        private PortaCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'porta';
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
            $errors['direction'][] = trans('PORTA_ERR_DIRECTION');
        }
        if ($text === '') {
            $errors['text'][] = trans('PORTA_ERR_TEXT_REQUIRED');
        }
        if ($key === '') {
            $errors['settings.key'][] = trans('PORTA_ERR_KEY_REQUIRED');
        }
        if ($text !== '' && !$this->cipher->hasLatinCharacters($text)) {
            $errors['text'][] = trans('PORTA_ERR_TEXT_LATIN');
        }
        if ($key !== '' && !$this->cipher->hasLatinCharacters($key)) {
            $errors['settings.key'][] = trans('PORTA_ERR_KEY_LATIN');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('PORTA_ERR_INVALID'), ['errors' => $errors]);
        }

        return [
            'ok' => true,
            'result' => $this->cipher->process($text, $key),
            'key' => $key,
        ];
    }
}
