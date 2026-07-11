<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента Punycode (кодирование/декодирование в браузере).
 */
final readonly class PunycodeCipherService
{
    /**
     * Возвращает UI-настройки инструмента: выбор варианта (домен / сырой RFC 3492).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-base-variant',
                'label'   => trans('PUNYCODE_SETTING_VARIANT_LABEL'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'domain', 'label' => trans('PUNYCODE_VARIANT_DOMAIN'), 'selected' => true],
                    ['value' => 'raw',    'label' => trans('PUNYCODE_VARIANT_RAW')],
                ],
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('PUNYCODE_TRUST_PURPOSE'),
            trans('PUNYCODE_TRUST_USES'),
            trans('CIPHER_TOOL_TRUST_UTF8'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
