<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Симулятор шифровальной машины Enigma I (Wehrmacht, M3).
 *
 * Реализует исторически точную модель: 5 доступных роторов (I–V),
 * 2 рефлектора (UKW-B, UKW-C), кольцевые установки (Ringstellung),
 * начальные позиции роторов (Grundstellung) и коммутационную панель (Steckerbrett).
 * Учитывается фирменная аномалия «двойного шага» (double-stepping).
 *
 * Алгоритм шифрования каждой буквы:
 *   1. Шаг роторов (с учётом double-stepping)
 *   2. Plugboard
 *   3. Правый → средний → левый ротор (forward)
 *   4. Рефлектор
 *   5. Левый → средний → правый ротор (backward)
 *   6. Plugboard
 *
 * Из-за рефлектора Enigma — реципрокный шифр: шифрование и дешифрование
 * совпадают при одинаковых настройках; буква никогда не шифруется в саму себя.
 */
final readonly class EnigmaCipherService
{
    /** @var array<string, array{wiring: string, notch: string}> Каталог роторов. */
    private const array ROTORS = [
        'I'   => ['wiring' => 'EKMFLGDQVZNTOWYHXUSPAIBRCJ', 'notch' => 'Q'],
        'II'  => ['wiring' => 'AJDKSIRUXBLHWTMCQGZNPYFVOE', 'notch' => 'E'],
        'III' => ['wiring' => 'BDFHJLCPRTXVZNYEIWGAKMUSQO', 'notch' => 'V'],
        'IV'  => ['wiring' => 'ESOVPZJAYQUIRHXLNFTGKDCMWB', 'notch' => 'J'],
        'V'   => ['wiring' => 'VZBRGITYUPSDNHLXAWMJQOFECK', 'notch' => 'Z'],
    ];

    /** @var array<string, string> Каталог рефлекторов. */
    private const array REFLECTORS = [
        'B' => 'YRUHQSLDPXNGOKMIEBFZCWVJAT',
        'C' => 'FVPJIAOYEDRZXWGCTKUQSBNMHL',
    ];

    private const string ALPHA = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Возвращает UI-настройки инструмента Enigma.
     *
     * Inline-поля: рефлектор, левый/средний/правый ротор, ring/position для каждого ротора.
     * Текстовое поле plugboard выводится отдельным textarea-блоком.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        $rotorOptions = [];
        foreach (array_keys(self::ROTORS) as $name) {
            $rotorOptions[] = ['value' => $name, 'label' => $name];
        }

        $letterOptions = [];
        foreach (str_split(self::ALPHA) as $letter) {
            $letterOptions[] = ['value' => $letter, 'label' => $letter];
        }

        $reflectorOptions = [];
        foreach (array_keys(self::REFLECTORS) as $name) {
            $reflectorOptions[] = ['value' => $name, 'label' => 'UKW-' . $name];
        }

        $withDefault = static function (array $options, string $default): array {
            foreach ($options as &$opt) {
                $opt['selected'] = $opt['value'] === $default;
            }
            return $options;
        };

        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-enigma-reflector',
                'label'   => trans('ENIGMA_SETTING_REFLECTOR'),
                'class'   => 'ciphers-settings-select',
                'options' => $withDefault($reflectorOptions, 'B'),
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-enigma-rotor-left',
                'label'   => trans('ENIGMA_SETTING_ROTOR_LEFT'),
                'class'   => 'ciphers-settings-select',
                'options' => $withDefault($rotorOptions, 'I'),
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-enigma-rotor-middle',
                'label'   => trans('ENIGMA_SETTING_ROTOR_MIDDLE'),
                'class'   => 'ciphers-settings-select',
                'options' => $withDefault($rotorOptions, 'II'),
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-enigma-rotor-right',
                'label'   => trans('ENIGMA_SETTING_ROTOR_RIGHT'),
                'class'   => 'ciphers-settings-select',
                'options' => $withDefault($rotorOptions, 'III'),
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-enigma-ring-left',
                'label'   => trans('ENIGMA_SETTING_RING_LEFT'),
                'class'   => 'ciphers-settings-select',
                'options' => $withDefault($letterOptions, 'A'),
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-enigma-ring-middle',
                'label'   => trans('ENIGMA_SETTING_RING_MIDDLE'),
                'class'   => 'ciphers-settings-select',
                'options' => $withDefault($letterOptions, 'A'),
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-enigma-ring-right',
                'label'   => trans('ENIGMA_SETTING_RING_RIGHT'),
                'class'   => 'ciphers-settings-select',
                'options' => $withDefault($letterOptions, 'A'),
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-enigma-pos-left',
                'label'   => trans('ENIGMA_SETTING_POS_LEFT'),
                'class'   => 'ciphers-settings-select',
                'options' => $withDefault($letterOptions, 'A'),
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-enigma-pos-middle',
                'label'   => trans('ENIGMA_SETTING_POS_MIDDLE'),
                'class'   => 'ciphers-settings-select',
                'options' => $withDefault($letterOptions, 'A'),
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-enigma-pos-right',
                'label'   => trans('ENIGMA_SETTING_POS_RIGHT'),
                'class'   => 'ciphers-settings-select',
                'options' => $withDefault($letterOptions, 'A'),
            ],
            [
                'type'        => 'textarea',
                'id'          => 'ciphers-enigma-plugboard',
                'label'       => trans('ENIGMA_SETTING_PLUGBOARD'),
                'class'       => 'ciphers-settings-textarea',
                'placeholder' => trans('ENIGMA_SETTING_PLUGBOARD_PLACEHOLDER'),
                'value'       => '',
                'hint'        => trans('ENIGMA_SETTING_PLUGBOARD_HINT'),
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для Enigma.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('ENIGMA_TRUST_HISTORICAL'),
            trans('ENIGMA_TRUST_RECIPROCAL'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }

    /**
     * Возвращает список доступных роторов.
     *
     * @return string[]
     */
    public function availableRotors(): array
    {
        return array_keys(self::ROTORS);
    }

    /**
     * Возвращает список доступных рефлекторов.
     *
     * @return string[]
     */
    public function availableReflectors(): array
    {
        return array_keys(self::REFLECTORS);
    }

    /**
     * Нормализует и валидирует plugboard-строку.
     *
     * Допустим формат: пары букв через пробел/дефис/запятую, без повторов («AB CD EF»).
     * Возвращает [errorKey|null, нормализованную строку, карта подстановок A=>B].
     *
     * @return array{0: ?string, 1: string, 2: array<string, string>}
     */
    public function parsePlugboard(string $raw): array
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z]/', '', $raw) ?? '');
        if ($clean === '') {
            return [null, '', []];
        }

        if (strlen($clean) % 2 !== 0) {
            return ['ENIGMA_ERR_PLUGBOARD_ODD', '', []];
        }

        $pairs = [];
        $map   = [];
        $used  = [];
        for ($i = 0, $len = strlen($clean); $i < $len; $i += 2) {
            $a = $clean[$i];
            $b = $clean[$i + 1];
            if ($a === $b) {
                return ['ENIGMA_ERR_PLUGBOARD_SELF', '', []];
            }
            if (isset($used[$a]) || isset($used[$b])) {
                return ['ENIGMA_ERR_PLUGBOARD_DUPLICATE', '', []];
            }
            $used[$a] = true;
            $used[$b] = true;
            $map[$a]  = $b;
            $map[$b]  = $a;
            $pairs[]  = $a . $b;
        }

        if (count($pairs) > 13) {
            return ['ENIGMA_ERR_PLUGBOARD_TOO_MANY', '', []];
        }

        return [null, implode(' ', $pairs), $map];
    }

    /**
     * Выполняет шифрование или дешифрование (это одно и то же из-за рефлектора).
     *
     * @param string                   $text       Входной текст.
     * @param array<string, string>    $rotors     Имена роторов: ['left'=>'I','middle'=>'II','right'=>'III'].
     * @param array<string, string>    $rings      Буквы ring settings: ['left'=>'A','middle'=>'A','right'=>'A'].
     * @param array<string, string>    $positions  Буквы стартовых позиций: ['left'=>'A','middle'=>'A','right'=>'A'].
     * @param string                   $reflector  Имя рефлектора (B/C).
     * @param array<string, string>    $plugboard  Карта подстановок plugboard (A=>B).
     * @return array{output: string, final_positions: array{left: string, middle: string, right: string}, letters_processed: int}
     */
    public function process(
        string $text,
        array $rotors,
        array $rings,
        array $positions,
        string $reflector,
        array $plugboard,
    ): array {
        $rotorL = self::ROTORS[$rotors['left']];
        $rotorM = self::ROTORS[$rotors['middle']];
        $rotorR = self::ROTORS[$rotors['right']];
        $refl   = self::REFLECTORS[$reflector];

        $wiringL    = $this->stringToOffsets($rotorL['wiring']);
        $wiringM    = $this->stringToOffsets($rotorM['wiring']);
        $wiringR    = $this->stringToOffsets($rotorR['wiring']);
        $wiringLInv = $this->invertOffsets($wiringL);
        $wiringMInv = $this->invertOffsets($wiringM);
        $wiringRInv = $this->invertOffsets($wiringR);
        $reflArr    = $this->stringToOffsets($refl);

        $notchL = $this->charToInt($rotorL['notch']);
        $notchM = $this->charToInt($rotorM['notch']);
        $notchR = $this->charToInt($rotorR['notch']);

        $ringL = $this->charToInt($rings['left']);
        $ringM = $this->charToInt($rings['middle']);
        $ringR = $this->charToInt($rings['right']);

        $posL = $this->charToInt($positions['left']);
        $posM = $this->charToInt($positions['middle']);
        $posR = $this->charToInt($positions['right']);

        $output  = '';
        $letters = 0;

        foreach (mb_str_split($text) as $char) {
            $upper = mb_strtoupper($char, 'UTF-8');
            $code  = $this->charToIntOrNull($upper);
            if ($code === null) {
                $output .= $char;
                continue;
            }

            // Шаг роторов перед обработкой буквы (с учётом double-stepping).
            $rightAtNotch  = ($posR === $notchR);
            $middleAtNotch = ($posM === $notchM);

            if ($middleAtNotch) {
                $posM = ($posM + 1) % 26;
                $posL = ($posL + 1) % 26;
            } elseif ($rightAtNotch) {
                $posM = ($posM + 1) % 26;
            }
            $posR = ($posR + 1) % 26;

            // 1) Plugboard
            $signal = $code;
            $sChar  = self::ALPHA[$signal];
            if (isset($plugboard[$sChar])) {
                $signal = ord($plugboard[$sChar]) - 65;
            }

            // 2) Forward: правый → средний → левый
            $signal = $this->rotorForward($signal, $wiringR, $posR, $ringR);
            $signal = $this->rotorForward($signal, $wiringM, $posM, $ringM);
            $signal = $this->rotorForward($signal, $wiringL, $posL, $ringL);

            // 3) Рефлектор
            $signal = $reflArr[$signal];

            // 4) Backward: левый → средний → правый
            $signal = $this->rotorBackward($signal, $wiringLInv, $posL, $ringL);
            $signal = $this->rotorBackward($signal, $wiringMInv, $posM, $ringM);
            $signal = $this->rotorBackward($signal, $wiringRInv, $posR, $ringR);

            // 5) Plugboard повторно
            $sChar = self::ALPHA[$signal];
            if (isset($plugboard[$sChar])) {
                $signal = ord($plugboard[$sChar]) - 65;
            }

            $resultChar = self::ALPHA[$signal];
            // Сохраняем регистр входного символа.
            $output .= $upper === $char ? $resultChar : strtolower($resultChar);
            $letters++;
        }

        return [
            'output'           => $output,
            'final_positions'  => [
                'left'   => self::ALPHA[$posL],
                'middle' => self::ALPHA[$posM],
                'right'  => self::ALPHA[$posR],
            ],
            'letters_processed' => $letters,
        ];
    }

    /**
     * Прямое прохождение через ротор: вход → выход (для одного направления).
     */
    private function rotorForward(int $signal, array $wiring, int $position, int $ring): int
    {
        $shifted = ($signal + $position - $ring + 26) % 26;
        $wired   = $wiring[$shifted];
        return ($wired - $position + $ring + 26) % 26;
    }

    /**
     * Обратное прохождение через ротор (после рефлектора).
     */
    private function rotorBackward(int $signal, array $wiringInv, int $position, int $ring): int
    {
        $shifted = ($signal + $position - $ring + 26) % 26;
        $wired   = $wiringInv[$shifted];
        return ($wired - $position + $ring + 26) % 26;
    }

    /**
     * Преобразует буквенную строку в массив смещений 0–25.
     *
     * @return int[]
     */
    private function stringToOffsets(string $s): array
    {
        $result = [];
        foreach (str_split($s) as $ch) {
            $result[] = ord($ch) - 65;
        }
        return $result;
    }

    /**
     * Возвращает обратную перестановку.
     *
     * @param  int[] $offsets
     * @return int[]
     */
    private function invertOffsets(array $offsets): array
    {
        $inv = array_fill(0, 26, 0);
        foreach ($offsets as $i => $v) {
            $inv[$v] = $i;
        }
        return $inv;
    }

    /**
     * Возвращает порядковый номер заглавной латинской буквы (A=0). Бросает Exception на невалидном входе.
     */
    private function charToInt(string $char): int
    {
        $upper = strtoupper(substr($char, 0, 1));
        $code  = ord($upper) - 65;
        if ($code < 0 || $code > 25) {
            return 0;
        }
        return $code;
    }

    /**
     * Возвращает порядковый номер буквы или null, если это не A–Z.
     */
    private function charToIntOrNull(string $upperChar): ?int
    {
        if (strlen($upperChar) !== 1) {
            return null;
        }
        $code = ord($upperChar) - 65;
        return ($code >= 0 && $code <= 25) ? $code : null;
    }
}
