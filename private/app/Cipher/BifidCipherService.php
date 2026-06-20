<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Бифид (Bifid) — классический полиграфический шифр на основе квадрата Полибия.
 *
 * Поддерживаемые алфавиты и их сетки:
 *  - en / it : 26 − J = 25 букв → квадрат 5×5, J приравнивается к I
 *  - pt      : 36 букв           → квадрат 6×6, без исключений
 *  - ru      : 33 + 3 цифры     → квадрат 6×6
 *  - de      : 29 + 7 цифр      → квадрат 6×6
 *  - es      : 27 + 9 цифр      → квадрат 6×6
 *  - tr      : 29 + 7 цифр      → квадрат 6×6
 *  - fr      : 40 + 9 цифр      → квадрат 7×7
 *
 * Цифры-заполнители (pad) дополняют алфавит до ближайшего точного квадрата:
 *  - в открытом тексте они отбрасываются (как и любые символы вне алфавита);
 *  - в шифротексте могут появляться — это нормально для языков с pad;
 *  - в расшифрованном тексте не появляются, т.к. на шифровании их не было.
 */
final readonly class BifidCipherService
{
    /**
     * Конфигурация сетки по алфавиту.
     * 'size'  — сторона квадрата;
     * 'omit'  — буква, удаляемая из алфавита ('' = нет);
     * 'merge' — карта замен при подготовке текста;
     * 'pad'   — цифры-заполнители для достижения N².
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
     * Создаёт экземпляр сервиса шифра Бифид.
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
        return $this->alphabetTool->detectAlphabet($text);
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
            trans('BIFID_TRUST_POLYBIUS_SQUARE'),
            trans('BIFID_TRUST_FRACTIONATION'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }

    /**
     * Выполняет шифрование или дешифрование текста шифром Бифид.
     *
     * Символы, не входящие в алфавит, отбрасываются. Pad-цифры в открытом тексте
     * также отбрасываются, но допускаются в шифротексте при расшифровке. Вывод —
     * в верхнем регистре.
     *
     * @param string $alphabet Код алфавита: 'en', 'it', 'pt', 'ru', 'de', 'es', 'tr', 'fr'.
     */
    public function process(string $text, string $key, string $alphabet, string $direction): string
    {
        $config  = self::GRID_CONFIG[$alphabet];
        [$matrix, $positions] = $this->buildSquare($key, $alphabet, $config);
        $letters = $this->prepareText($text, $alphabet, $config, allowPad: $direction === 'decrypt');

        if ($letters === []) {
            return '';
        }

        $result = $direction === 'decrypt'
            ? $this->decrypt($letters, $matrix, $positions)
            : $this->encrypt($letters, $matrix, $positions);

        return $this->caseFolder->toUpper($result, $alphabet);
    }

    /**
     * Шифрует список букв методом фракционирования координат.
     *
     * @param  string[]                               $letters   Буквы открытого текста (нижний регистр).
     * @param  array<int, array<int, string>>         $matrix    Квадрат N×N.
     * @param  array<string, array{row:int,col:int}>  $positions Карта позиций.
     */
    private function encrypt(array $letters, array $matrix, array $positions): string
    {
        $rows = [];
        $cols = [];

        foreach ($letters as $char) {
            $pos    = $positions[$char];
            $rows[] = $pos['row'];
            $cols[] = $pos['col'];
        }

        $s      = array_merge($rows, $cols);
        $n      = count($letters);
        $result = '';

        for ($k = 0; $k < $n; $k++) {
            $result .= $matrix[$s[2 * $k]][$s[2 * $k + 1]];
        }

        return $result;
    }

    /**
     * Дешифрует список букв шифра Бифид.
     *
     * @param  string[]                               $letters   Буквы шифротекста (нижний регистр).
     * @param  array<int, array<int, string>>         $matrix    Квадрат N×N.
     * @param  array<string, array{row:int,col:int}>  $positions Карта позиций.
     */
    private function decrypt(array $letters, array $matrix, array $positions): string
    {
        $t = [];

        foreach ($letters as $char) {
            $pos  = $positions[$char];
            $t[]  = $pos['row'];
            $t[]  = $pos['col'];
        }

        $n      = count($letters);
        $result = '';

        for ($k = 0; $k < $n; $k++) {
            $result .= $matrix[$t[$k]][$t[$n + $k]];
        }

        return $result;
    }

    /**
     * Строит квадрат N×N из ключа и алфавита.
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

        $matrix    = array_chunk($sequence, $config['size']);
        $positions = [];

        foreach ($matrix as $row => $line) {
            foreach ($line as $col => $char) {
                $positions[$char] = ['row' => $row, 'col' => $col];
            }
        }

        // Добавляем позиции для объединённых букв (например, j → позиция i)
        foreach ($config['merge'] as $from => $to) {
            if (isset($positions[$to])) {
                $positions[$from] = $positions[$to];
            }
        }

        return [$matrix, $positions];
    }

    /**
     * Извлекает и нормализует буквы из текста согласно алфавиту и конфигурации замен.
     *
     * @param  array{size:int,omit:string,merge:array<string,string>,pad:string[]} $config
     * @param  bool                                                                $allowPad Разрешить ли pad-цифры (true при дешифровке, false при шифровании и в ключе).
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
}
