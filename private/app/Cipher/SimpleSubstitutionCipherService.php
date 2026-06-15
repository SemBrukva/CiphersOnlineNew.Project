<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра простой замены с поддержкой нескольких алфавитов.
 */
final readonly class SimpleSubstitutionCipherService
{
    /**
     * Создаёт экземпляр сервиса шифра простой замены.
     */
    public function __construct(
        private ?AlphabetCatalog $catalog = null
    ) {
    }

    /**
     * Возвращает UI-настройки инструмента для шифра простой замены.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        $catalog = $this->alphabetCatalog();

        $letters = static fn(string $code): string => implode('', array_map(
            static fn(string $c): string => mb_strtoupper($c),
            $catalog->alphabet($code)
        ));

        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-alphabet',
                'label'   => trans('CIPHER_TOOL_SETTING_ALPHABET'),
                'class'   => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'en', 'label' => trans('LANG_EN'), 'attrs' => ['data-letters' => $letters('en')], 'selected' => true],
                    ['value' => 'ru', 'label' => trans('LANG_RU'), 'attrs' => ['data-letters' => $letters('ru')]],
                    ['value' => 'es', 'label' => trans('LANG_ES'), 'attrs' => ['data-letters' => $letters('es')]],
                    ['value' => 'de', 'label' => trans('LANG_DE'), 'attrs' => ['data-letters' => $letters('de')]],
                    ['value' => 'fr', 'label' => trans('LANG_FR'), 'attrs' => ['data-letters' => $letters('fr')]],
                    ['value' => 'it', 'label' => trans('LANG_IT'), 'attrs' => ['data-letters' => $letters('it')]],
                    ['value' => 'pt', 'label' => trans('LANG_PT'), 'attrs' => ['data-letters' => $letters('pt')]],
                    ['value' => 'tr', 'label' => trans('LANG_TR'), 'attrs' => ['data-letters' => implode('', $catalog->alphabet('tr'))]],
                ],
            ],
            [
                'type'         => 'text',
                'id'           => 'ciphers-key',
                'label'        => trans('SIMPLE_SUBSTITUTION_SETTING_KEY'),
                'class'        => 'ciphers-settings-input',
                'placeholder'  => trans('SIMPLE_SUBSTITUTION_SETTING_KEY_PLACEHOLDER'),
                'value'        => 'QWERTYUIOPASDFGHJKLZXCVBNM',
                'shuffleKey'   => true,
                'shuffleLabel' => trans('SIMPLE_SUBSTITUTION_SHUFFLE'),
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для шифра простой замены.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('SIMPLE_SUBSTITUTION_TRUST_TYPE'),
            trans('SIMPLE_SUBSTITUTION_TRUST_KEY'),
            trans('CIPHER_TOOL_TRUST_MULTI_ALPHA'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }

    /**
     * Определяет код алфавита по символам ключа.
     *
     * Сравнивает отсортированный набор символов ключа с каждым алфавитом каталога.
     * Возвращает null, если ключ не является перестановкой ни одного известного алфавита.
     */
    public function detectAlphabetFromKey(string $key): ?string
    {
        $normalized = mb_strtolower($key);
        $keyChars   = [];
        $len        = mb_strlen($normalized);

        for ($i = 0; $i < $len; $i++) {
            $keyChars[] = mb_substr($normalized, $i, 1);
        }

        sort($keyChars);

        foreach ($this->alphabetCatalog()->all() as $code => $letters) {
            $sorted = $letters;
            sort($sorted);

            if ($keyChars === $sorted) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Проверяет, содержит ли текст хотя бы один символ из указанного алфавита.
     */
    public function textContainsAlphabetChars(string $text, string $alphabet): bool
    {
        $set    = array_flip($this->alphabetCatalog()->alphabet($alphabet));
        $length = mb_strlen($text);

        for ($i = 0; $i < $length; $i++) {
            if (isset($set[mb_strtolower(mb_substr($text, $i, 1))])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Выполняет шифрование/дешифрование текста шифром простой замены.
     */
    public function process(string $text, string $alphabet, string $key, string $direction): string
    {
        $alphabetData  = $this->alphabetCatalog()->alphabet($alphabet);
        $normalizedKey = mb_strtolower($key);
        $keyData       = [];
        $keyLength     = mb_strlen($normalizedKey);

        for ($i = 0; $i < $keyLength; $i++) {
            $keyData[] = mb_substr($normalizedKey, $i, 1);
        }

        $indexMap    = array_flip($alphabetData);
        $keyIndexMap = array_flip($keyData);
        $output      = '';
        $length      = mb_strlen($text);

        for ($i = 0; $i < $length; $i++) {
            $char    = mb_substr($text, $i, 1);
            $lower   = mb_strtolower($char);
            $isUpper = $char !== $lower;

            if ($direction === 'encrypt') {
                if (!isset($indexMap[$lower])) {
                    $output .= $char;
                    continue;
                }
                $result = $keyData[(int) $indexMap[$lower]];
            } else {
                if (!isset($keyIndexMap[$lower])) {
                    $output .= $char;
                    continue;
                }
                $result = $alphabetData[(int) $keyIndexMap[$lower]];
            }

            $output .= $isUpper ? mb_strtoupper($result) : $result;
        }

        return $output;
    }

    /**
     * Возвращает каталог алфавитов.
     */
    private function alphabetCatalog(): AlphabetCatalog
    {
        return $this->catalog ?? new AlphabetCatalog();
    }
}
