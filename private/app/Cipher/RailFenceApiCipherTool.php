<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра Rail Fence.
 */
final readonly class RailFenceApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента Rail Fence.
     */
    public function __construct(
        private RailFenceCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'rail-fence';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $payload): array
    {
        $text = (string) ($payload['text'] ?? '');
        $direction = (string) ($payload['direction'] ?? 'encrypt');
        $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $rails = (int) ($settings['rails'] ?? $settings['shift'] ?? 3);

        $errors = [];
        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = trans('RAIL_FENCE_ERR_DIRECTION');
        }
        if ($text === '') {
            $errors['text'][] = trans('RAIL_FENCE_ERR_TEXT_REQUIRED');
        }
        if ($rails < RailFenceCipherService::MIN_RAILS || $rails > RailFenceCipherService::MAX_RAILS) {
            $errors['settings.rails'][] = trans('RAIL_FENCE_ERR_RAILS', ['min' => RailFenceCipherService::MIN_RAILS, 'max' => RailFenceCipherService::MAX_RAILS]);
        }

        if ($errors !== []) {
            throw new ValidationFailedException('The given data was invalid.', ['errors' => $errors]);
        }

        $rails = $this->cipher->normalizeRails($rails);

        $warning = null;
        if ($rails >= mb_strlen($text)) {
            $warning = trans('RAIL_FENCE_WARNING_RAILS_TOO_HIGH');
        }

        return [
            'ok' => true,
            'result' => $this->cipher->process($text, $rails, $direction),
            'rails' => $rails,
            'warning' => $warning,
        ];
    }
}
