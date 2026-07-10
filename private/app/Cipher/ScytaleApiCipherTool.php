<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра Scytale (скитала).
 */
final readonly class ScytaleApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента Scytale.
     */
    public function __construct(
        private ScytaleCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'scytale';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $payload): array
    {
        $text = (string) ($payload['text'] ?? '');
        $direction = (string) ($payload['direction'] ?? 'encrypt');
        $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $columns = (int) ($settings['columns'] ?? $settings['shift'] ?? 4);

        $errors = [];
        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = trans('SCYTALE_ERR_DIRECTION');
        }
        if ($text === '') {
            $errors['text'][] = trans('SCYTALE_ERR_TEXT_REQUIRED');
        }
        if ($columns < ScytaleCipherService::MIN_COLUMNS || $columns > ScytaleCipherService::MAX_COLUMNS) {
            $errors['settings.columns'][] = trans('SCYTALE_ERR_COLUMNS', ['min' => ScytaleCipherService::MIN_COLUMNS, 'max' => ScytaleCipherService::MAX_COLUMNS]);
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('SCYTALE_ERR_INVALID'), ['errors' => $errors]);
        }

        $columns = $this->cipher->normalizeColumns($columns);

        $warning = null;
        if ($columns >= mb_strlen($text)) {
            $warning = trans('SCYTALE_WARNING_COLUMNS_TOO_HIGH');
        }

        return [
            'ok' => true,
            'result' => $this->cipher->process($text, $columns, $direction),
            'columns' => $columns,
            'warning' => $warning,
        ];
    }
}
