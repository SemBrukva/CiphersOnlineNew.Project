<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис настроек инструмента «Книжный шифр» (Book cipher).
 *
 * Само преобразование выполняется на клиенте (JS-декодер `decoders/book.js`),
 * поэтому здесь описываются только UI-настройки и элементы блока доверия.
 */
final readonly class BookCipherService
{
    /**
     * Возвращает UI-настройки инструмента: схему адресации, разделитель вывода
     * и текстовое поле «референсного текста» (книги), играющего роль ключа.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-book-scheme',
                'label'   => trans('BOOK_SCHEME_LABEL'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'word-index', 'label' => trans('BOOK_SCHEME_WORD_INDEX'), 'selected' => true],
                    ['value' => 'beale',      'label' => trans('BOOK_SCHEME_BEALE')],
                    ['value' => 'line-word',  'label' => trans('BOOK_SCHEME_LINE_WORD')],
                    ['value' => 'char-index', 'label' => trans('BOOK_SCHEME_CHAR_INDEX')],
                ],
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-delimiter',
                'label'   => trans('CIPHER_TOOL_SETTING_DELIMITER'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'space', 'label' => trans('CIPHER_TOOL_SETTING_SPACE'), 'selected' => true],
                    ['value' => 'dash',  'label' => '-'],
                    ['value' => 'comma', 'label' => ','],
                    ['value' => 'slash', 'label' => '/'],
                ],
            ],
            [
                'type'        => 'textarea',
                'id'          => 'ciphers-key',
                'label'       => trans('BOOK_REFERENCE_LABEL'),
                'class'       => 'ciphers-settings-textarea',
                'placeholder' => trans('BOOK_REFERENCE_PLACEHOLDER'),
                'hint'        => trans('BOOK_REFERENCE_HINT'),
                'value'       => '',
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для книжного шифра.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('BOOK_TRUST_SCHEMES'),
            trans('BOOK_TRUST_KEY_TEXT'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
