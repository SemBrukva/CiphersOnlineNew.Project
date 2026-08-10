<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра нигилистов (Nihilist substitution) — комбинация квадрата
 * Полибия с ключом и аддитивного числового ключа.
 *
 * Шифрование выполняется в два этапа:
 *  1. Ключевым словом строится смешанный квадрат Полибия N×N. Каждая буква
 *     открытого текста заменяется двузначным числом «строка·10 + столбец»
 *     (строки и столбцы нумеруются от 1). Для en/it это классический квадрат
 *     5×5 (J объединяется с I), для остальных алфавитов — 6×6/7×7 с цифрами-
 *     заполнителями, как в шифре Бифид.
 *  2. Второе ключевое слово тем же квадратом переводится в поток чисел и
 *     циклически повторяется. Число открытого текста складывается с числом
 *     ключа — сумма и есть группа шифротекста.
 *
 * Так как сумма может превышать 99, группы шифротекста имеют переменную
 * длину и разделяются пробелом. При расшифровке из строки извлекаются все
 * числовые группы, из каждой вычитается число ключа, а полученная координата
 * отображается обратно в букву квадрата.
 */
final readonly class NihilistCipherService
{
    /**
     * Конфигурация квадрата по алфавиту (зеркалит Бифид).
     * 'size'  — сторона квадрата;
     * 'omit'  — буква, удаляемая из алфавита ('' = нет);
     * 'merge' — карта замен при подготовке текста;
     * 'pad'   — цифры-заполнители, дополняющие алфавит до N².
     *
     * @var array<string, array{size: int, omit: string, merge: array<string, string>, pad: string[]}>
     */
    private const array GRID_CONFIG = [
        'en' => ['size' => 5, 'omit' => 'j', 'merge' => ['j' => 'i'], 'pad' => []],
        'it' => ['size' => 5, 'omit' => 'j', 'merge' => ['j' => 'i'], 'pad' => []],
        'pt' => ['size' => 6, 'omit' => '',  'merge' => [],            'pad' => []],
        'ru' => ['size' => 6, 'omit' => '',  'merge' => [],            'pad' => ['1', '2', '3']],
        'de' => ['size' => 6, 'omit' => '',  'merge' => [],            'pad' => ['1', '2', '3', '4', '5', '6', '7']],
        'es' => ['size' => 6, 'omit' => '',  'merge' => [],            'pad' => ['1', '2', '3', '4', '5', '6', '7', '8', '9']],
        'tr' => ['size' => 6, 'omit' => '',  'merge' => [],            'pad' => ['1', '2', '3', '4', '5', '6', '7']],
        'fr' => ['size' => 7, 'omit' => '',  'merge' => [],            'pad' => ['1', '2', '3', '4', '5', '6', '7', '8', '9']],
    ];

    /**
     * Создаёт экземпляр сервиса шифра нигилистов.
     */
    public function __construct(
        private AlphabetCatalog $catalog,
        private AlphabetTool    $alphabetTool,
        private CaseFolder      $caseFolder
    ) {
    }

    /**
     * Возвращает коды поддерживаемых алфавитов.
     *
     * @return string[]
     */
    public function supportedAlphabetCodes(): array
    {
        return array_keys(self::GRID_CONFIG);
    }

    /**
     * Проверяет, поддерживается ли алфавит.
     */
    public function supportsAlphabet(string $alphabet): bool
    {
        return isset(self::GRID_CONFIG[$alphabet]);
    }

    /**
     * Автоопределяет алфавит по тексту, откатываясь на 'en' для неподдерживаемых.
     */
    public function detectAlphabet(string $text): string
    {
        $detected = $this->alphabetTool->detectAlphabet($text);

        return $this->supportsAlphabet($detected) ? $detected : 'en';
    }

    /**
     * Проверяет, содержит ли текст хотя бы одну букву квадрата выбранного алфавита.
     */
    public function hasSquareLetters(string $text, string $alphabet): bool
    {
        return $this->supportsAlphabet($alphabet)
            && $this->prepareLetters($text, $alphabet, allowPad: false) !== [];
    }

    /**
     * Проверяет, содержит ли текст хотя бы одну числовую группу (для расшифровки).
     */
    public function hasNumberGroups(string $text): bool
    {
        return preg_match('/\d+/', $text) === 1;
    }

    /**
     * Возвращает UI-настройки инструмента.
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
                    ['value' => 'auto', 'label' => trans('CIPHER_TOOL_SETTING_AUTO'), 'selected' => true],
                    ['value' => 'en',   'label' => trans('LANG_EN')],
                    ['value' => 'ru',   'label' => trans('LANG_RU')],
                    ['value' => 'de',   'label' => trans('LANG_DE')],
                    ['value' => 'es',   'label' => trans('LANG_ES')],
                    ['value' => 'fr',   'label' => trans('LANG_FR')],
                    ['value' => 'it',   'label' => trans('LANG_IT')],
                    ['value' => 'pt',   'label' => trans('LANG_PT')],
                    ['value' => 'tr',   'label' => trans('LANG_TR')],
                ],
            ],
            [
                'type'        => 'text',
                'id'          => 'ciphers-key',
                'label'       => trans('NIHILIST_SETTING_SQUARE_KEY'),
                'class'       => 'ciphers-settings-input',
                'placeholder' => trans('NIHILIST_SETTING_SQUARE_KEY_PLACEHOLDER'),
                'value'       => 'ZEBRAS',
            ],
            [
                'type'        => 'text',
                'id'          => 'ciphers-nihilist-key',
                'label'       => trans('NIHILIST_SETTING_ADDITIVE_KEY'),
                'class'       => 'ciphers-settings-input',
                'placeholder' => trans('NIHILIST_SETTING_ADDITIVE_KEY_PLACEHOLDER'),
                'value'       => 'RUSSIAN',
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
            trans('NIHILIST_TRUST_POLYBIUS_SQUARE'),
            trans('NIHILIST_TRUST_ADDITIVE_KEY'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }

    /**
     * Выполняет шифрование или дешифрование текста шифром нигилистов.
     *
     * @param string $squareKey   Ключ для построения квадрата Полибия.
     * @param string $additiveKey Аддитивное ключевое слово (числовой поток).
     * @param string $alphabet    Код алфавита: 'en', 'it', 'ru', 'de', 'es', 'fr', 'pt', 'tr'.
     */
    public function process(string $text, string $squareKey, string $additiveKey, string $alphabet, string $direction): string
    {
        return $this->processDetailed($text, $squareKey, $additiveKey, $alphabet, $direction)['result'];
    }

    /**
     * Выполняет обработку и дополнительно возвращает данные для визуализации:
     * построенный квадрат и пошаговое разложение plain/key/cipher.
     *
     * @return array{
     *   result: string,
     *   size: int,
     *   square: array<int, array<int, string>>,
     *   steps: array<int, array{symbol: string, code: int, key_symbol: string, key_code: int, cipher: int}>,
     *   direction: string
     * }
     */
    public function processDetailed(string $text, string $squareKey, string $additiveKey, string $alphabet, string $direction): array
    {
        if (!$this->supportsAlphabet($alphabet)) {
            return ['result' => '', 'size' => 0, 'square' => [], 'steps' => [], 'direction' => $direction];
        }

        $config = self::GRID_CONFIG[$alphabet];
        [$matrix, $positions] = $this->buildSquare($squareKey, $alphabet, $config);

        $keyCodes = $this->keyStream($additiveKey, $alphabet, $config, $positions);

        $data = $direction === 'decrypt'
            ? $this->decrypt($text, $keyCodes, $matrix, $config, $alphabet)
            : $this->encrypt($text, $keyCodes, $positions, $alphabet);

        return [
            'result'    => $data['result'],
            'size'      => $config['size'],
            'square'    => $this->upperMatrix($matrix, $alphabet),
            'steps'     => $data['steps'],
            'direction' => $direction,
        ];
    }

    /**
     * Шифрует текст: буквы → координаты → сложение с потоком ключа.
     *
     * @param  array<int, array{code: int, symbol: string}>  $keyCodes  Поток чисел ключа.
     * @param  array<string, array{row:int,col:int}>         $positions Карта позиций символов.
     * @return array{result: string, steps: array<int, array{symbol: string, code: int, key_symbol: string, key_code: int, cipher: int}>}
     */
    private function encrypt(string $text, array $keyCodes, array $positions, string $alphabet): array
    {
        $letters = $this->prepareLetters($text, $alphabet, allowPad: false);

        if ($letters === [] || $keyCodes === []) {
            return ['result' => '', 'steps' => []];
        }

        $keyLen = count($keyCodes);
        $groups = [];
        $steps  = [];

        foreach ($letters as $i => $letter) {
            $pos       = $positions[$letter];
            $plainCode = ($pos['row'] + 1) * 10 + ($pos['col'] + 1);
            $key       = $keyCodes[$i % $keyLen];
            $sum       = $plainCode + $key['code'];

            $groups[] = $sum;
            $steps[]  = [
                'symbol'     => $this->caseFolder->toUpper($letter, $alphabet),
                'code'       => $plainCode,
                'key_symbol' => $key['symbol'],
                'key_code'   => $key['code'],
                'cipher'     => $sum,
            ];
        }

        return ['result' => implode(' ', $groups), 'steps' => $steps];
    }

    /**
     * Дешифрует текст: числовые группы → вычитание потока ключа → буквы.
     *
     * @param  array<int, array{code: int, symbol: string}>  $keyCodes Поток чисел ключа.
     * @param  array<int, array<int, string>>                $matrix   Квадрат N×N.
     * @param  array{size:int,omit:string,merge:array<string,string>,pad:string[]} $config
     * @return array{result: string, steps: array<int, array{symbol: string, code: int, key_symbol: string, key_code: int, cipher: int}>}
     */
    private function decrypt(string $text, array $keyCodes, array $matrix, array $config, string $alphabet): array
    {
        preg_match_all('/\d+/', $text, $matches);
        $groups = array_map('intval', $matches[0]);

        if ($groups === [] || $keyCodes === []) {
            return ['result' => '', 'steps' => []];
        }

        $size   = $config['size'];
        $keyLen = count($keyCodes);
        $chars  = '';
        $steps  = [];

        foreach ($groups as $i => $group) {
            $key       = $keyCodes[$i % $keyLen];
            $plainCode = $group - $key['code'];
            $row       = intdiv($plainCode, 10) - 1;
            $col       = $plainCode % 10 - 1;

            if ($row < 0 || $row >= $size || $col < 0 || $col >= $size) {
                continue;
            }

            $char   = $matrix[$row][$col];
            $chars .= $char;

            $steps[] = [
                'symbol'     => $this->caseFolder->toUpper($char, $alphabet),
                'code'       => $plainCode,
                'key_symbol' => $key['symbol'],
                'key_code'   => $key['code'],
                'cipher'     => $group,
            ];
        }

        return ['result' => $this->caseFolder->toUpper($chars, $alphabet), 'steps' => $steps];
    }

    /**
     * Строит поток чисел из аддитивного ключа: каждая буква ключа → её координата.
     *
     * @param  array{size:int,omit:string,merge:array<string,string>,pad:string[]} $config
     * @param  array<string, array{row:int,col:int}> $positions Карта позиций символов квадрата.
     * @return array<int, array{code: int, symbol: string}>
     */
    private function keyStream(string $additiveKey, string $alphabet, array $config, array $positions): array
    {
        $letters = $this->prepareLetters($additiveKey, $alphabet, allowPad: false);
        $stream  = [];

        foreach ($letters as $letter) {
            if (!isset($positions[$letter])) {
                continue;
            }

            $pos      = $positions[$letter];
            $stream[] = [
                'code'   => ($pos['row'] + 1) * 10 + ($pos['col'] + 1),
                'symbol' => $this->caseFolder->toUpper($letter, $alphabet),
            ];
        }

        return $stream;
    }

    /**
     * Строит квадрат N×N из ключа и алфавита (зеркалит Бифид).
     *
     * @param  array{size:int,omit:string,merge:array<string,string>,pad:string[]} $config
     * @return array{
     *   0: array<int, array<int, string>>,
     *   1: array<string, array{row:int,col:int}>
     * }
     */
    private function buildSquare(string $key, string $alphabet, array $config): array
    {
        $letters    = $this->alphabetLetters($alphabet, $config);
        $keyLetters = $this->prepareLetters($key, $alphabet, allowPad: false);

        $used     = [];
        $sequence = [];

        foreach (array_merge($keyLetters, $letters) as $char) {
            if (!isset($used[$char])) {
                $used[$char] = true;
                $sequence[]  = $char;
            }
        }

        $matrix    = array_chunk($sequence, $config['size']);
        $positions = [];

        foreach ($matrix as $row => $line) {
            foreach ($line as $col => $char) {
                $positions[$char] = ['row' => $row, 'col' => $col];
            }
        }

        // Позиции для объединённых букв (например, j → позиция i).
        foreach ($config['merge'] as $from => $to) {
            if (isset($positions[$to])) {
                $positions[$from] = $positions[$to];
            }
        }

        return [$matrix, $positions];
    }

    /**
     * Извлекает и нормализует буквы текста согласно алфавиту и замене.
     *
     * @param  bool $allowPad Разрешить ли pad-цифры (false для открытого текста и ключей).
     * @return string[]
     */
    private function prepareLetters(string $text, string $alphabet, bool $allowPad): array
    {
        $config  = self::GRID_CONFIG[$alphabet];
        $letters = $this->alphabetLetters($alphabet, $config);

        if (!$allowPad && $config['pad'] !== []) {
            $padSet  = array_flip($config['pad']);
            $letters = array_values(array_filter(
                $letters,
                static fn (string $letter): bool => !isset($padSet[$letter])
            ));
        }

        $valid  = array_flip($letters);
        $result = [];

        foreach (mb_str_split($this->caseFolder->toLower($text, $alphabet)) as $char) {
            $char = $config['merge'][$char] ?? $char;

            if (isset($valid[$char])) {
                $result[] = $char;
            }
        }

        return $result;
    }

    /**
     * Возвращает список букв алфавита с учётом исключений (omit) и заполнителей (pad).
     *
     * @param  array{size:int,omit:string,merge:array<string,string>,pad:string[]} $config
     * @return string[]
     */
    private function alphabetLetters(string $alphabet, array $config): array
    {
        $letters = $this->catalog->alphabet($alphabet);

        if ($config['omit'] !== '') {
            $omit    = $config['omit'];
            $letters = array_values(array_filter($letters, static fn (string $l): bool => $l !== $omit));
        }

        if ($config['pad'] !== []) {
            $letters = array_merge($letters, $config['pad']);
        }

        return $letters;
    }

    /**
     * Приводит символы квадрата к верхнему регистру для отображения.
     *
     * @param  array<int, array<int, string>> $matrix Квадрат N×N.
     * @return array<int, array<int, string>>
     */
    private function upperMatrix(array $matrix, string $alphabet): array
    {
        return array_map(
            fn (array $row): array => array_map(
                fn (string $char): string => $this->caseFolder->toUpper($char, $alphabet),
                $row
            ),
            $matrix
        );
    }
}
