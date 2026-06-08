<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра столбцовой перестановки.
 */
final readonly class ColumnarTranspositionApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента столбцовой перестановки.
     */
    public function __construct(
        private ColumnarTranspositionCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'columnar-transposition';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $payload): array
    {
        $text = (string) ($payload['text'] ?? '');
        $direction = (string) ($payload['direction'] ?? 'encrypt');
        $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $key = $this->cipher->normalizeKey((string) ($settings['key'] ?? ''));
        $keyLength = $this->cipher->keyLength($key);

        $errors = [];
        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = trans('COLUMNAR_ERR_DIRECTION');
        }
        if ($text === '') {
            $errors['text'][] = trans('COLUMNAR_ERR_TEXT_REQUIRED');
        }
        if ($keyLength < ColumnarTranspositionCipherService::MIN_KEY_LENGTH) {
            $errors['settings.key'][] = trans('COLUMNAR_ERR_KEY_MIN', ['min' => ColumnarTranspositionCipherService::MIN_KEY_LENGTH]);
        }
        if ($keyLength > ColumnarTranspositionCipherService::MAX_KEY_LENGTH) {
            $errors['settings.key'][] = trans('COLUMNAR_ERR_KEY_MAX', ['max' => ColumnarTranspositionCipherService::MAX_KEY_LENGTH]);
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('COLUMNAR_ERR_INVALID'), ['errors' => $errors]);
        }

        return [
            'ok' => true,
            'result' => $this->cipher->process($text, $key, $direction),
            'key' => $key,
        ];
    }
}
