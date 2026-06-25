<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\EnigmaCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты симулятора шифровальной машины Enigma.
 */
final class EnigmaCipherServiceTest extends TestCase
{
    private EnigmaCipherService $service;

    protected function setUp(): void
    {
        $this->service = new EnigmaCipherService();
    }

    /**
     * Каждое поле rotors/rings/positions.
     *
     * @return array{rotors: array<string,string>, rings: array<string,string>, positions: array<string,string>}
     */
    private function defaultSettings(string $posL = 'A', string $posM = 'A', string $posR = 'A'): array
    {
        return [
            'rotors'    => ['left' => 'I', 'middle' => 'II', 'right' => 'III'],
            'rings'     => ['left' => 'A', 'middle' => 'A', 'right' => 'A'],
            'positions' => ['left' => $posL, 'middle' => $posM, 'right' => $posR],
        ];
    }

    /**
     * Известный исторический тест: шифрование «AAAAA» с роторами I-II-III, рефлектором B,
     * кольцами и позициями A-A-A, без plugboard должно дать «BDZGO».
     */
    public function testKnownAnswerAAAAA(): void
    {
        $s = $this->defaultSettings();
        $r = $this->service->process('AAAAA', $s['rotors'], $s['rings'], $s['positions'], 'B', []);
        self::assertSame('BDZGO', $r['output']);
    }

    /**
     * Реципрокность Enigma: те же настройки шифруют и расшифровывают.
     */
    public function testReciprocity(): void
    {
        $s = $this->defaultSettings();
        $enc = $this->service->process('HELLOWORLD', $s['rotors'], $s['rings'], $s['positions'], 'B', []);
        $dec = $this->service->process($enc['output'], $s['rotors'], $s['rings'], $s['positions'], 'B', []);
        self::assertSame('HELLOWORLD', $dec['output']);
    }

    /**
     * Реципрокность с plugboard.
     */
    public function testReciprocityWithPlugboard(): void
    {
        $s = $this->defaultSettings();
        $plugboard = ['A' => 'B', 'B' => 'A', 'C' => 'D', 'D' => 'C'];
        $enc = $this->service->process('ATTACKATDAWN', $s['rotors'], $s['rings'], $s['positions'], 'B', $plugboard);
        $dec = $this->service->process($enc['output'], $s['rotors'], $s['rings'], $s['positions'], 'B', $plugboard);
        self::assertSame('ATTACKATDAWN', $dec['output']);
    }

    /**
     * Буква никогда не шифруется в саму себя.
     */
    public function testLetterNeverEncryptsToItself(): void
    {
        $s = $this->defaultSettings();
        // Шифруем длинную строку из одной буквы и проверяем, что результат не содержит её.
        $input  = str_repeat('A', 60);
        $result = $this->service->process($input, $s['rotors'], $s['rings'], $s['positions'], 'B', []);

        $output = $result['output'];
        for ($i = 0, $len = strlen($output); $i < $len; $i++) {
            self::assertNotSame('A', $output[$i], "Letter at position $i should not be 'A'");
        }
    }

    /**
     * Double-stepping: после 26 нажатий с позиций A-A-A средний ротор делает 2 шага подряд.
     */
    public function testDoubleStepping(): void
    {
        $s = $this->defaultSettings();
        // Ротор III имеет notch на V (позиция 21). Начнём с U (20) — следующее
        // нажатие сделает правый ротор V (на notch), потом A — и средний ротор шагнёт.
        // Затем со средним ротором на E (notch для II) сработает double-stepping.
        $s['positions'] = ['left' => 'A', 'middle' => 'D', 'right' => 'U'];
        $result = $this->service->process('AAAAA', $s['rotors'], $s['rings'], $s['positions'], 'B', []);
        // После 5 шагов правый ушёл на U+5=Z (последняя буква), средний должен
        // продвинуться 2 раза (один раз от notch правого, один раз от собственного double-step).
        self::assertSame('Z', $result['final_positions']['right']);
        self::assertSame('F', $result['final_positions']['middle']);
        self::assertSame('B', $result['final_positions']['left']);
    }

    /**
     * Не-латинские символы и пробелы должны проходить через машину неизменными.
     */
    public function testPreservesNonLatinCharacters(): void
    {
        $s = $this->defaultSettings();
        $r = $this->service->process('HELLO WORLD!', $s['rotors'], $s['rings'], $s['positions'], 'B', []);
        self::assertStringContainsString(' ', $r['output']);
        self::assertStringContainsString('!', $r['output']);
        self::assertSame(10, $r['letters_processed']);
    }

    /**
     * Сохранение регистра входного символа.
     */
    public function testPreservesCase(): void
    {
        $s = $this->defaultSettings();
        $r = $this->service->process('Hello', $s['rotors'], $s['rings'], $s['positions'], 'B', []);
        // Первая буква в верхнем регистре, остальные строчные.
        self::assertSame(strtoupper($r['output'][0]), $r['output'][0]);
        self::assertSame(strtolower($r['output'][1]), $r['output'][1]);
    }

    /**
     * Финальные позиции корректно вычисляются (короткое сообщение).
     */
    public function testFinalPositionsForShortMessage(): void
    {
        $s = $this->defaultSettings();
        $r = $this->service->process('HELLO', $s['rotors'], $s['rings'], $s['positions'], 'B', []);
        // 5 букв → правый ротор на F (A+5).
        self::assertSame('F', $r['final_positions']['right']);
        self::assertSame('A', $r['final_positions']['middle']);
        self::assertSame('A', $r['final_positions']['left']);
        self::assertSame(5, $r['letters_processed']);
    }

    /**
     * Смена рефлектора с UKW-B на UKW-C даёт другой шифр.
     */
    public function testDifferentReflectorProducesDifferentResult(): void
    {
        $s = $this->defaultSettings();
        $b = $this->service->process('HELLO', $s['rotors'], $s['rings'], $s['positions'], 'B', []);
        $c = $this->service->process('HELLO', $s['rotors'], $s['rings'], $s['positions'], 'C', []);
        self::assertNotSame($b['output'], $c['output']);
    }

    /**
     * Смена ring setting меняет шифр.
     */
    public function testRingSettingAffectsOutput(): void
    {
        $s = $this->defaultSettings();
        $a = $this->service->process('HELLO', $s['rotors'], $s['rings'], $s['positions'], 'B', []);
        $s['rings']['right'] = 'B';
        $b = $this->service->process('HELLO', $s['rotors'], $s['rings'], $s['positions'], 'B', []);
        self::assertNotSame($a['output'], $b['output']);
    }

    /**
     * Парсер plugboard принимает корректную пару.
     */
    public function testParsePlugboardValidPairs(): void
    {
        [$err, $normalized, $map] = $this->service->parsePlugboard('AB CD EF');
        self::assertNull($err);
        self::assertSame('AB CD EF', $normalized);
        self::assertSame('B', $map['A']);
        self::assertSame('A', $map['B']);
        self::assertSame('D', $map['C']);
    }

    /**
     * Plugboard игнорирует нелатинские разделители.
     */
    public function testParsePlugboardIgnoresSeparators(): void
    {
        [$err, $normalized] = $this->service->parsePlugboard('a-b,c d');
        self::assertNull($err);
        self::assertSame('AB CD', $normalized);
    }

    /**
     * Plugboard отвергает нечётное число букв.
     */
    public function testParsePlugboardRejectsOddLength(): void
    {
        [$err] = $this->service->parsePlugboard('ABC');
        self::assertSame('ENIGMA_ERR_PLUGBOARD_ODD', $err);
    }

    /**
     * Plugboard отвергает дубликат буквы.
     */
    public function testParsePlugboardRejectsDuplicate(): void
    {
        [$err] = $this->service->parsePlugboard('AB AC');
        self::assertSame('ENIGMA_ERR_PLUGBOARD_DUPLICATE', $err);
    }

    /**
     * Plugboard отвергает соединение буквы с самой собой.
     */
    public function testParsePlugboardRejectsSelfPair(): void
    {
        [$err] = $this->service->parsePlugboard('AA');
        self::assertSame('ENIGMA_ERR_PLUGBOARD_SELF', $err);
    }

    /**
     * Plugboard отвергает более 13 пар (получается 14+).
     */
    public function testParsePlugboardRejectsTooManyPairs(): void
    {
        // 14 пар (28 букв) — нужно ровно 14 пар уникальных букв.
        [$err] = $this->service->parsePlugboard('AB CD EF GH IJ KL MN OP QR ST UV WX YZ AC');
        self::assertNotNull($err);
    }

    /**
     * availableRotors возвращает I–V.
     */
    public function testAvailableRotors(): void
    {
        self::assertSame(['I', 'II', 'III', 'IV', 'V'], $this->service->availableRotors());
    }

    /**
     * availableReflectors возвращает B и C.
     */
    public function testAvailableReflectors(): void
    {
        self::assertSame(['B', 'C'], $this->service->availableReflectors());
    }

    /**
     * Длинная строка успешно шифруется/дешифруется с plugboard.
     */
    public function testLongStringRoundTrip(): void
    {
        $s = $this->defaultSettings('M', 'C', 'K');
        $s['rings'] = ['left' => 'B', 'middle' => 'C', 'right' => 'D'];
        $plugboard  = ['A' => 'M', 'M' => 'A', 'F' => 'I', 'I' => 'F', 'N' => 'V', 'V' => 'N'];

        $input = 'THE QUICK BROWN FOX JUMPS OVER THE LAZY DOG';
        $enc = $this->service->process($input, $s['rotors'], $s['rings'], $s['positions'], 'C', $plugboard);
        $dec = $this->service->process($enc['output'], $s['rotors'], $s['rings'], $s['positions'], 'C', $plugboard);
        self::assertSame($input, $dec['output']);
    }
}
