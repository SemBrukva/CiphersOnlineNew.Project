<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент XOR-шифра.
 */
final readonly class XorApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента XOR-шифра.
     */
    public function __construct(
        private XorCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'xor';
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
        $keyFormat = (string) ($settings['xor_key_format'] ?? 'text');

        if (!in_array($keyFormat, ['text', 'hex'], true)) {
            $keyFormat = 'text';
        }

        $errors = [];
        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = trans('XOR_ERROR_DIRECTION');
        }
        if ($text === '') {
            $errors['text'][] = trans('XOR_ERROR_TEXT_REQUIRED');
        }
        if ($key === '') {
            $errors['settings.key'][] = trans('XOR_ERROR_KEY_REQUIRED');
        } elseif ($keyFormat === 'hex') {
            $keyHex = preg_replace('/[^0-9a-fA-F]/', '', $key);
            if ($keyHex === null || $keyHex === '' || strlen($keyHex) % 2 !== 0) {
                $errors['settings.key'][] = trans('XOR_ERROR_INVALID_HEX_KEY');
            }
        }

        if ($direction === 'decrypt' && $text !== '') {
            $hex = preg_replace('/[^0-9a-fA-F]/', '', $text);
            if ($hex === null || $hex === '' || strlen($hex) % 2 !== 0) {
                $errors['text'][] = trans('XOR_ERROR_INVALID_HEX');
            }
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('XOR_ERROR_INVALID'), ['errors' => $errors]);
        }

        return [
            'ok'        => true,
            'result'    => $this->cipher->process($text, $key, $direction, $keyFormat),
            'key'       => $key,
            'key_format'=> $keyFormat,
        ];
    }
}
