<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент шифра Альберти.
 */
final readonly class AlbertiApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента шифра Альберти.
     */
    public function __construct(
        private AlbertiCipherService $cipher
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'alberti';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $payload): array
    {
        $text      = (string) ($payload['text'] ?? '');
        $direction = (string) ($payload['direction'] ?? 'encrypt');
        $settings  = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $keyword   = trim((string) ($settings['key'] ?? ''));
        $index     = strtoupper(trim((string) ($settings['alberti_index'] ?? 'A')));

        if ($index === '' || strlen($index) !== 1 || !ctype_alpha($index)) {
            $index = 'A';
        }

        $errors = [];
        if (!in_array($direction, ['encrypt', 'decrypt'], true)) {
            $errors['direction'][] = trans('ALBERTI_ERR_DIRECTION');
        }
        if ($text === '') {
            $errors['text'][] = trans('ALBERTI_ERR_TEXT_REQUIRED');
        }
        if ($text !== '' && !$this->cipher->hasLatinCharacters($text)) {
            $errors['text'][] = trans('ALBERTI_ERR_TEXT_LATIN');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('ALBERTI_ERR_INVALID'), ['errors' => $errors]);
        }

        $result = $this->cipher->process($text, $keyword, $index, $direction);

        return [
            'ok'             => true,
            'result'         => $result,
            'inner_alphabet' => $this->cipher->innerAlphabetString($keyword),
            'index_offset'   => $this->cipher->computeOffset($index),
        ];
    }
}
