<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента «Танцующие человечки».
 */
final readonly class DancingMenCipherService
{
    /**
     * Возвращает UI-настройки инструмента: выбор языка алфавита.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-alphabet',
                'label'   => trans('CIPHER_TOOL_SETTING_ALPHABET'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'en', 'label' => trans('LANG_EN'), 'selected' => true],
                    ['value' => 'ru', 'label' => trans('LANG_RU')],
                ],
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для инструмента.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('DANCING_MEN_TRUST_SHERLOCK'),
            trans('DANCING_MEN_TRUST_KEYLESS'),
            trans('DANCING_MEN_TRUST_VISUAL'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
