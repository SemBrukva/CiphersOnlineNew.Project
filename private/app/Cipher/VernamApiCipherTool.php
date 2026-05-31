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
            $errors['direction'][] = trans('VERNAM_ERROR_DIRECTION');
        }
        if ($text === '') {
            $errors['text'][] = trans('VERNAM_ERROR_TEXT_REQUIRED');
        }
        if ($key === '') {
            $errors['settings.key'][] = trans('VERNAM_ERROR_KEY_REQUIRED');
        }

        if ($errors !== []) {
            throw new ValidationFailedException('The given data was invalid.', ['errors' => $errors]);
        }

        $warning = null;
        if ($direction === 'encrypt' && strlen($key) < strlen($text)) {
            $warning = trans('VERNAM_WARNING_KEY_SHORT');
        }

        return [
            'ok' => true,
            'result' => $this->cipher->process($text, $key, $direction),
            'key' => $key,
            'warning' => $warning,
        ];
    }
}
