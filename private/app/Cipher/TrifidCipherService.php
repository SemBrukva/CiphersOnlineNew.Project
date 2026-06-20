<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Трифид (Trifid) — классический полиграфический шифр на основе куба Полибия 3×3×3.
 *
 * Поддерживаемые алфавиты (все используют куб 3×3×3 = 27 ячеек):
 *  - en / it : 26 − J = 25 букв + 2 цифровых заполнителя → 27
 *  - es      : 27 букв (a–z + ñ), без заполнителей → 27
 *  - de      : 29 − J − Q = 27 букв (J→I, Q→K) → 27
 *  - tr      : 29 − J − Ğ = 27 букв (J→C, Ğ→G) → 27
 *  - pt      : 36 − 9 ударных гласных = 27 букв (á/à/ã→a, é/ê→e, í→i, ó/ô→o, ú→u) → 27
 *  - fr      : 40 − 13 диакритических = 27 букв (все акцентированные → основа, ç сохраняется) → 27
 *
 * PT и FR после слияний дают одинаковый набор a–z + ç — снятие диакритики
 * стандартно для классических шифров. RU не поддерживается: потребовало бы
 * 6 спорных кириллических слияний (в т.ч. ы→и, э→е).
 *
 * Турецкий регистр обрабатывается через CaseFolder: I↔ı и İ↔i.
 *
 * Алгоритм шифрования:
 *  1. Для каждой буквы открытого текста получить координаты (layer, row, col) в кубе.
 *  2. Сформировать вектор S = [layer_1,...,layer_n, row_1,...,row_n, col_1,...,col_n].
 *  3. Разбить S на последовательные тройки: (S[3k], S[3k+1], S[3k+2]).
 *  4. Каждую тройку → поиск в кубе → буква шифротекста.
 *
 * Дешифрование — обратная операция:
 *  1. Для каждой буквы шифротекста получить координаты.
 *  2. Сформировать вектор T из чередующихся layer/row/col.
 *  3. Разбить T на три части: A = T[0..n-1], B = T[n..2n-1], C = T[2n..3n-1].
 *  4. Буква открытого текста[k] = поиск в кубе (A[k], B[k], C[k]).
 */
final readonly class TrifidCipherService
{
    /**
     * Конфигурация куба по алфавиту.
     * 'size'  — сторона куба (всегда 3, т.к. 3³ = 27);
     * 'omit'  — буквы, удаляемые из алфавита;
     * 'merge' — карта замен при подготовке текста (сливаемая буква → целевая);
     * 'pad'   — цифры-заполнители для достижения 27.
     *
     * @var array<string, array{size: int, omit: string[], merge: array<string, string>, pad: string[]}>
     */
    private const array GRID_CONFIG = [
        'en' => ['size' => 3, 'omit' => ['j'],       'merge' => ['j' => 'i'],             'pad' => ['1', '2']],
        'it' => ['size' => 3, 'omit' => ['j'],       'merge' => ['j' => 'i'],             'pad' => ['1', '2']],
        'es' => ['size' => 3, 'omit' => [],           'merge' => [],                       'pad' => []],
        'de' => ['size' => 3, 'omit' => ['j', 'q'],  'merge' => ['j' => 'i', 'q' => 'k'], 'pad' => []],
        'tr' => ['size' => 3, 'omit' => ['j', 'ğ'],  'merge' => ['j' => 'c', 'ğ' => 'g'], 'pad' => []],
        'pt' => ['size' => 3, 'omit' => ['á', 'à', 'ã', 'é', 'ê', 'í', 'ó', 'ô', 'ú'],
                              'merge' => ['á' => 'a', 'à' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e',
                                          'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'ú' => 'u'],
                              'pad' => []],
        'fr' => ['size' => 3, 'omit' => ['à', 'â', 'é', 'è', 'ê', 'ë', 'î', 'ï', 'ô', 'ù', 'û', 'ü', 'ÿ'],
                              'merge' => ['à' => 'a', 'â' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e',
                                          'ë' => 'e', 'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ù' => 'u',
                                          'û' => 'u', 'ü' => 'u', 'ÿ' => 'y'],
                              'pad' => []],
    ];

    /**
     * Создаёт экземпляр сервиса шифра Трифид.
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
     * Автоопределяет алфавит по тексту через общий AlphabetTool.
     */
    public function detectAlphabet(string $text): string
    {
        $detected = $this->alphabetTool->detectAlphabet($text);

        return in_array($detected, $this->supportedAlphabetCodes(), true) ? $detected : 'en';
    }

    /**
     * Проверяет, содержит ли текст хотя бы один символ выбранного алфавита.
     */
    public function hasAlphabetCharacters(string $text, string $alphabet): bool
    {
        return $this->alphabetTool->hasAlphabetCharacters($text, $alphabet);
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
                'label'       => trans('CIPHER_TOOL_SETTING_KEY'),
                'class'       => 'ciphers-settings-input',
                'placeholder' => trans('CIPHER_TOOL_SETTING_KEY_PLACEHOLDER'),
                'value'       => '',
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
            trans('TRIFID_TRUST_POLYBIUS_CUBE'),
            trans('TRIFID_TRUST_FRACTIONATION'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }

    /**
     * Выполняет шифрование или дешифрование текста шифром Трифид.
     *
     * Символы, не входящие в алфавит, отбрасываются. Вывод — в верхнем регистре.
     *
     * @param string $alphabet Код алфавита: 'en', 'it', 'es', 'de', 'tr', 'pt', 'fr'.
     */
    public function process(string $text, string $key, string $alphabet, string $direction): string
    {
        $config  = self::GRID_CONFIG[$alphabet];
        [$cube, $positions] = $this->buildCube($key, $alphabet, $config);
        $letters = $this->prepareText($text, $alphabet, $config, allowPad: $direction === 'decrypt');

        if ($letters === []) {
            return '';
        }

        $result = $direction === 'decrypt'
            ? $this->decrypt($letters, $cube, $positions)
            : $this->encrypt($letters, $cube, $positions);

        return $this->caseFolder->toUpper($result, $alphabet);
    }

    /**
     * Шифрует список букв методом фракционирования координат куба.
     *
     * @param  string[]                                            $letters   Буквы открытого текста (нижний регистр).
     * @param  array<int, array<int, array<int, string>>>          $cube      Куб N×N×N.
     * @param  array<string, array{layer:int,row:int,col:int}>     $positions Карта позиций.
     */
    private function encrypt(array $letters, array $cube, array $positions): string
    {
        $layers = [];
        $rows   = [];
        $cols   = [];

        foreach ($letters as $char) {
            $pos      = $positions[$char];
            $layers[] = $pos['layer'];
            $rows[]   = $pos['row'];
            $cols[]   = $pos['col'];
        }

        $s      = array_merge($layers, $rows, $cols);
        $n      = count($letters);
        $result = '';

        for ($k = 0; $k < $n; $k++) {
            $result .= $cube[$s[3 * $k]][$s[3 * $k + 1]][$s[3 * $k + 2]];
        }

        return $result;
    }

    /**
     * Дешифрует список букв шифра Трифид.
     *
     * @param  string[]                                            $letters   Буквы шифротекста (нижний регистр).
     * @param  array<int, array<int, array<int, string>>>          $cube      Куб N×N×N.
     * @param  array<string, array{layer:int,row:int,col:int}>     $positions Карта позиций.
     */
    private function decrypt(array $letters, array $cube, array $positions): string
    {
        $t = [];

        foreach ($letters as $char) {
            $pos  = $positions[$char];
            $t[]  = $pos['layer'];
            $t[]  = $pos['row'];
            $t[]  = $pos['col'];
        }

        $n      = count($letters);
        $result = '';

        for ($k = 0; $k < $n; $k++) {
            $result .= $cube[$t[$k]][$t[$n + $k]][$t[2 * $n + $k]];
        }

        return $result;
    }

    /**
     * Строит куб N×N×N из ключа и алфавита.
     *
     * @param  array{size:int,omit:string[],merge:array<string,string>,pad:string[]} $config
     * @return array{
     *   0: array<int, array<int, array<int, string>>>,
     *   1: array<string, array{layer:int,row:int,col:int}>
     * }
     */
    private function buildCube(string $key, string $alphabet, array $config): array
    {
        $letters    = $this->alphabetLetters($alphabet, $config);
        $keyLetters = $this->prepareText($key, $alphabet, $config, allowPad: false);

        $used     = [];
        $sequence = [];

        foreach ($keyLetters as $char) {
            if (!isset($used[$char])) {
                $used[$char]  = true;
                $sequence[]   = $char;
            }
        }

        foreach ($letters as $char) {
            if (!isset($used[$char])) {
                $used[$char]  = true;
                $sequence[]   = $char;
            }
        }

        $n         = $config['size'];
        $cube      = [];
        $positions = [];

        foreach ($sequence as $idx => $char) {
            $layer = intdiv($idx, $n * $n);
            $row   = intdiv($idx % ($n * $n), $n);
            $col   = $idx % $n;

            $cube[$layer][$row][$col] = $char;
            $positions[$char] = ['layer' => $layer, 'row' => $row, 'col' => $col];
        }

        foreach ($config['merge'] as $from => $to) {
            if (isset($positions[$to])) {
                $positions[$from] = $positions[$to];
            }
        }

        return [$cube, $positions];
    }

    /**
     * Извлекает и нормализует буквы из текста согласно алфавиту и конфигурации замен.
     *
     * @param  array{size:int,omit:string[],merge:array<string,string>,pad:string[]} $config
     * @param  bool                                                                $allowPad Разрешить ли pad-цифры.
     * @return string[]
     */
    private function prepareText(string $text, string $alphabet, array $config, bool $allowPad): array
    {
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
     * @param  array{size:int,omit:string[],merge:array<string,string>,pad:string[]} $config
     * @return string[]
     */
    private function alphabetLetters(string $alphabet, array $config): array
    {
        $letters = $this->catalog->alphabet($alphabet);

        if ($config['omit'] !== []) {
            $omitSet = array_flip($config['omit']);
            $letters = array_values(array_filter($letters, static fn (string $l): bool => !isset($omitSet[$l])));
        }

        if ($config['pad'] !== []) {
            $letters = array_merge($letters, $config['pad']);
        }

        return $letters;
    }
}
