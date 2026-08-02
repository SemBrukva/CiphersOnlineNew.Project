<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра ADFGVX — комбинация квадрата Полибия 6×6 (фракционирование)
 * и столбцовой перестановки по ключевому слову.
 *
 * Шифрование выполняется в два этапа:
 *  1. Каждый символ открытого текста заменяется парой букв из набора A, D, F, G, V, X
 *     (координаты в смешанном квадрате 6×6, построенном из ключа квадрата).
 *  2. Полученная строка из букв ADFGVX подвергается столбцовой перестановке
 *     по второму ключу (ключу транспозиции).
 *
 * Квадрат 6×6 вмещает ровно 36 символов, поэтому для каждого алфавита он
 * дополняется цифрами-заполнителями (pad) до 36 ячеек. В отличие от Бифид,
 * цифры здесь — полноценные шифруемые символы (историческая особенность ADFGVX,
 * отличающая его от предшественника ADFGX 5×5). Французский алфавит (40 букв)
 * не поддерживается: он не помещается в квадрат 6×6.
 */
final readonly class AdfgvxCipherService
{
    /**
     * Метки строк и столбцов квадрата.
     *
     * @var string[]
     */
    private const array LABELS = ['A', 'D', 'F', 'G', 'V', 'X'];

    /**
     * Конфигурация квадрата по алфавиту.
     * 'pad' — цифры-заполнители, дополняющие алфавит до 36 символов (6×6).
     *
     * @var array<string, array{pad: string[]}>
     */
    private const array GRID_CONFIG = [
        'en' => ['pad' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9']],
        'it' => ['pad' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9']],
        'ru' => ['pad' => ['0', '1', '2']],
        'de' => ['pad' => ['0', '1', '2', '3', '4', '5', '6']],
        'es' => ['pad' => ['1', '2', '3', '4', '5', '6', '7', '8', '9']],
        'pt' => ['pad' => []],
        'tr' => ['pad' => ['0', '1', '2', '3', '4', '5', '6']],
    ];

    /**
     * Создаёт экземпляр сервиса шифра ADFGVX.
     */
    public function __construct(
        private AlphabetCatalog $catalog,
        private AlphabetTool    $alphabetTool,
        private CaseFolder      $caseFolder,
        private ColumnarTranspositionCipherService $transposition
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
     * Проверяет, поддерживается ли алфавит (помещается ли в квадрат 6×6).
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
     * Проверяет, содержит ли текст хотя бы один символ квадрата (буква или pad-цифра).
     */
    public function hasSquareCharacters(string $text, string $alphabet): bool
    {
        return $this->supportsAlphabet($alphabet)
            && $this->extractPlainSymbols($text, $alphabet) !== [];
    }

    /**
     * Проверяет, содержит ли текст хотя бы одну метку ADFGVX (для расшифровки).
     */
    public function hasLabels(string $text): bool
    {
        return $this->extractLabels($text) !== [];
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
                    ['value' => 'it',   'label' => trans('LANG_IT')],
                    ['value' => 'pt',   'label' => trans('LANG_PT')],
                    ['value' => 'tr',   'label' => trans('LANG_TR')],
                ],
            ],
            [
                'type'        => 'text',
                'id'          => 'ciphers-key',
                'label'       => trans('ADFGVX_SETTING_SQUARE_KEY'),
                'class'       => 'ciphers-settings-input',
                'placeholder' => trans('ADFGVX_SETTING_SQUARE_KEY_PLACEHOLDER'),
                'value'       => 'PRIVACY',
            ],
            [
                'type'        => 'text',
                'id'          => 'ciphers-adfgvx-key',
                'label'       => trans('ADFGVX_SETTING_TRANSPOSITION_KEY'),
                'class'       => 'ciphers-settings-input',
                'placeholder' => trans('ADFGVX_SETTING_TRANSPOSITION_KEY_PLACEHOLDER'),
                'value'       => 'BATTLE',
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
            trans('ADFGVX_TRUST_POLYBIUS_SQUARE'),
            trans('ADFGVX_TRUST_TRANSPOSITION'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }

    /**
     * Выполняет шифрование или дешифрование текста шифром ADFGVX.
     *
     * @param string $squareKey        Ключ для построения квадрата 6×6.
     * @param string $transpositionKey Ключевое слово столбцовой перестановки.
     * @param string $alphabet         Код алфавита: 'en', 'it', 'ru', 'de', 'es', 'pt', 'tr'.
     */
    public function process(string $text, string $squareKey, string $transpositionKey, string $alphabet, string $direction): string
    {
        if (!$this->supportsAlphabet($alphabet)) {
            return '';
        }

        [$matrix, $positions] = $this->buildSquare($squareKey, $alphabet);

        return $direction === 'decrypt'
            ? $this->decrypt($text, $transpositionKey, $matrix, $alphabet)
            : $this->encrypt($text, $transpositionKey, $positions, $alphabet);
    }

    /**
     * Шифрует текст: фракционирование через квадрат + столбцовая перестановка.
     *
     * @param array<string, array{row:int,col:int}> $positions Карта позиций символов в квадрате.
     */
    private function encrypt(string $text, string $transpositionKey, array $positions, string $alphabet): string
    {
        $symbols = $this->extractPlainSymbols($text, $alphabet);

        if ($symbols === []) {
            return '';
        }

        $fractionated = '';
        foreach ($symbols as $symbol) {
            $pos           = $positions[$symbol];
            $fractionated .= self::LABELS[$pos['row']] . self::LABELS[$pos['col']];
        }

        return $this->transposition->process($fractionated, $transpositionKey, 'encrypt');
    }

    /**
     * Дешифрует текст: обратная столбцовая перестановка + обратное фракционирование.
     *
     * @param array<int, array<int, string>> $matrix Квадрат 6×6.
     */
    private function decrypt(string $text, string $transpositionKey, array $matrix, string $alphabet): string
    {
        $labels = $this->extractLabels($text);

        if ($labels === []) {
            return '';
        }

        $fractionated = $this->transposition->process(implode('', $labels), $transpositionKey, 'decrypt');
        $chars        = str_split($fractionated);
        $labelIndex   = array_flip(self::LABELS);

        $result = '';
        $count  = intdiv(count($chars), 2);

        for ($k = 0; $k < $count; $k++) {
            $row = $labelIndex[$chars[2 * $k]] ?? null;
            $col = $labelIndex[$chars[2 * $k + 1]] ?? null;

            if ($row === null || $col === null) {
                continue;
            }

            $result .= $matrix[$row][$col];
        }

        return $this->caseFolder->toUpper($result, $alphabet);
    }

    /**
     * Строит квадрат 6×6 из ключа и алфавита.
     *
     * @return array{
     *   0: array<int, array<int, string>>,
     *   1: array<string, array{row:int,col:int}>
     * }
     */
    private function buildSquare(string $key, string $alphabet): array
    {
        $symbols    = $this->squareSymbols($alphabet);
        $keySymbols = $this->extractPlainSymbols($key, $alphabet);

        $used     = [];
        $sequence = [];

        foreach (array_merge($keySymbols, $symbols) as $symbol) {
            if (!isset($used[$symbol])) {
                $used[$symbol] = true;
                $sequence[]    = $symbol;
            }
        }

        $matrix    = array_chunk($sequence, 6);
        $positions = [];

        foreach ($matrix as $row => $line) {
            foreach ($line as $col => $symbol) {
                $positions[$symbol] = ['row' => $row, 'col' => $col];
            }
        }

        return [$matrix, $positions];
    }

    /**
     * Извлекает символы открытого текста, входящие в квадрат (буквы и pad-цифры).
     *
     * @return string[]
     */
    private function extractPlainSymbols(string $text, string $alphabet): array
    {
        $valid  = array_flip($this->squareSymbols($alphabet));
        $result = [];

        foreach (mb_str_split($this->caseFolder->toLower($text, $alphabet)) as $char) {
            if (isset($valid[$char])) {
                $result[] = $char;
            }
        }

        return $result;
    }

    /**
     * Извлекает из текста только метки ADFGVX (в верхнем регистре).
     *
     * @return string[]
     */
    private function extractLabels(string $text): array
    {
        $valid  = array_flip(self::LABELS);
        $result = [];

        foreach (str_split(strtoupper($text)) as $char) {
            if (isset($valid[$char])) {
                $result[] = $char;
            }
        }

        return $result;
    }

    /**
     * Возвращает 36 символов квадрата для алфавита (буквы + pad-цифры).
     *
     * @return string[]
     */
    private function squareSymbols(string $alphabet): array
    {
        $letters = $this->catalog->alphabet($alphabet);
        $pad     = self::GRID_CONFIG[$alphabet]['pad'];

        return array_merge($letters, $pad);
    }
}
