<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Реестр конфигурации инструментов шифрования и декодирования.
 *
 * Централизует связь между slug инструмента и его UI/API-конфигурацией.
 */
final readonly class ToolRegistry
{
    /**
     * Создаёт экземпляр реестра инструментов.
     */
    public function __construct(
        private AffineCipherService $affineCipher,
        private AtbashCipherService $atbashCipher,
        private BeaufortCipherService $beaufortCipher,
        private CaesarCipherService $caesarCipher,
        private GronsfeldCipherService $gronsfeldCipher,
        private PlayfairCipherService $playfairCipher,
        private VigenereCipherService $vigenereCipher,
        private VernamCipherService $vernamCipher,
        private BaconCipherService $baconCipher,
        private Rot13CipherService $rot13Cipher,
        private A1z26CipherService $a1z26Cipher,
        private RailFenceCipherService $railFenceCipher,
        private ColumnarTranspositionCipherService $columnarTranspositionCipher,
        private PolybiusSquareCipherService $polybiusSquareCipher,
        private HillCipherService $hillCipher,
        private MorseCipherService $morseCipher
    ) {
    }

    /**
     * Возвращает примерные значения (chips) для инструмента.
     * Все примеры должны быть универсальными по языкам, поэтому решено делать их на английском.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function exampleChips(string $toolSlug): array
    {
        $canonicalSlug = $this->canonicalSlug($toolSlug);

        return match ($canonicalSlug) {
            'encoding/base64' => [
                ['label' => 'JSON', 'value' => '{"id":42,"role":"admin","active":true}'],
                ['label' => 'Unicode', 'value' => 'Café naïve résumé ☕'],
                ['label' => 'Header', 'value' => 'Authorization: Basic dXNlcjpwYXNzd29yZA=='],
            ],
            'encoding/hex' => [
                ['label' => 'JSON', 'value' => '{"id":42,"role":"admin","active":true}'],
                ['label' => 'Unicode', 'value' => 'Привет мир 👋'],
                ['label' => 'Hex Bytes', 'value' => '48 65 6c 6c 6f 2c 20 77 6f 72 6c 64 21'],
            ],
            'encoding/url-encode' => [
                ['label' => 'URL', 'value' => 'https://example.com/search?q=smart tools'],
                ['label' => 'Params', 'value' => 'email=test@example.com&name=John Doe'],
                ['label' => 'Unicode', 'value' => 'Привет мир'],
            ],
            'encoding/binary-converter' => [
                ['label' => 'Hello', 'value' => 'Hello'],
                ['label' => 'Binary', 'value' => '01001000 01101001'],
                ['label' => 'Cool', 'value' => '01000011 01101111 01101111 01101100'],
            ],
            'encoding/ascii-converter' => [
                ['label' => 'ASCII', 'value' => '67 105 112 104 101 114'],
                ['label' => 'Hello', 'value' => 'Hello'],
                ['label' => 'Digits', 'value' => '49 50 51 33'],
            ],
            'encoding/unicode-converter' => [
                ['label' => 'Escape', 'value' => '\\u041f\\u0440\\u0438\\u0432\\u0435\\u0442'],
                ['label' => 'Codepoint', 'value' => 'U+1F600'],
                ['label' => 'Emoji', 'value' => '😀'],
            ],
            'encoding/jwt-decoder' => [
                ['label' => 'JWT', 'value' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyIjoiam9obiIsImFkbWluIjp0cnVlfQ.signature'],
                ['label' => 'Demo', 'value' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJyb2xlIjoiZWRpdG9yIiwiaWF0IjoxNzAwMDAwMDAwfQ.demo'],
                ['label' => 'ID', 'value' => 'eyJhbGciOiJIUzI1NiJ9.eyJpZCI6MTIzLCJuYW1lIjoiQWxpY2UifQ.test'],
            ],
            'classical-ciphers/playfair' => [
                ['label' => 'Military', 'value' => 'DEFEND THE EAST WALL', 'alphabet' => 'en', 'key' => 'MONARCHY'],
                ['label' => 'Classic',  'value' => 'HELLO WORLD',          'alphabet' => 'en', 'key' => 'PLAYFAIR'],
                ['label' => 'Secret',   'value' => 'HIDE THE GOLD',        'alphabet' => 'en', 'key' => 'SECRET'],
            ],
            'classical-ciphers/caesar' => [
                ['label' => 'Military', 'value' => 'DEFEND THE EAST WALL', 'alphabet' => 'en', 'shift' => 3],
                ['label' => 'ROT-13',   'value' => 'HELLO WORLD',          'alphabet' => 'en', 'shift' => 13],
                ['label' => 'Secret',   'value' => 'ATTACK AT DAWN',       'alphabet' => 'en', 'shift' => 7],
            ],
            'classical-ciphers/rot13' => [
                ['label' => 'Classic', 'value' => 'HELLO WORLD'],
                ['label' => 'Decode',  'value' => 'URYYB JBEYQ', 'direction' => 'decrypt'],
                ['label' => 'Email',   'value' => 'contact@example.com'],
            ],
            'classical-ciphers/beaufort' => [
                ['label' => 'Military', 'value' => 'DEFEND THE EAST WALL', 'alphabet' => 'en', 'key' => 'SECRET'],
                ['label' => 'Classic',  'value' => 'HELLO WORLD',          'alphabet' => 'en', 'key' => 'BEAUFORT'],
                ['label' => 'Secret',   'value' => 'ATTACK AT DAWN',       'alphabet' => 'en', 'key' => 'KEY'],
            ],
            'classical-ciphers/gronsfeld' => [
                ['label' => 'Military', 'value' => 'HELLO WORLD',    'alphabet' => 'en', 'key' => '9871'],
                ['label' => 'Classic',  'value' => 'ATTACK AT DAWN', 'alphabet' => 'en', 'key' => '1234'],
                ['label' => 'Secret',   'value' => 'HIDE THE GOLD',  'alphabet' => 'en', 'key' => '5678'],
            ],
            'classical-ciphers/vigenere' => [
                ['label' => 'Military', 'value' => 'DEFEND THE EAST WALL', 'alphabet' => 'en', 'key' => 'SECRET'],
                ['label' => 'Classic',  'value' => 'HELLO WORLD',          'alphabet' => 'en', 'key' => 'VIGENERE'],
                ['label' => 'Secret',   'value' => 'ATTACK AT DAWN',       'alphabet' => 'en', 'key' => 'KEY'],
            ],
            'classical-ciphers/vernam' => [
                ['label' => 'Military', 'value' => 'DEFEND THE EAST WALL', 'alphabet' => 'en', 'key' => 'SECRET'],
                ['label' => 'Classic',  'value' => 'HELLO WORLD',          'alphabet' => 'en', 'key' => 'VERNAM'],
                ['label' => 'Secret',   'value' => 'ATTACK AT DAWN',       'alphabet' => 'en', 'key' => 'KEY'],
            ],
            'classical-ciphers/atbash' => [
                ['label' => 'Classic',  'value' => 'HELLO WORLD',    'alphabet' => 'en'],
                ['label' => 'Military', 'value' => 'ATTACK AT DAWN', 'alphabet' => 'en'],
                ['label' => 'Secret',   'value' => 'HIDE THE GOLD',  'alphabet' => 'en'],
            ],
            'classical-ciphers/bacon' => [
                ['label' => 'Classic',  'value' => 'HELLO WORLD',    'alphabet' => 'en'],
                ['label' => 'Military', 'value' => 'ATTACK AT DAWN', 'alphabet' => 'en'],
                ['label' => 'Stego',    'value' => 'HELLO',          'alphabet' => 'en', 'key' => 'The quick brown fox jumps over the lazy dog'],
            ],
            'classical-ciphers/a1z26' => [
                ['label' => 'Classic',  'value' => 'HELLO WORLD',    'alphabet' => 'en', 'direction' => 'encrypt'],
                ['label' => 'Military', 'value' => 'ATTACK AT DAWN', 'alphabet' => 'en', 'direction' => 'encrypt'],
                ['label' => 'Decode',   'value' => '8-5-12-12-15',   'alphabet' => 'en', 'direction' => 'decrypt', 'delimiter' => 'dash'],
            ],
            'classical-ciphers/rail-fence' => [
                ['label' => 'Classic',  'value' => 'WE ARE DISCOVERED', 'shift' => 3],
                ['label' => 'Military', 'value' => 'ATTACK AT DAWN',    'shift' => 4],
                ['label' => 'Decode',   'value' => 'WECRLTEERDSOEEFEAOCAIVDEN', 'shift' => 3, 'direction' => 'decrypt'],
            ],
            'classical-ciphers/columnar-transposition' => [
                ['label' => 'Classic',  'value' => 'WE ARE DISCOVERED', 'key' => 'SECRET'],
                ['label' => 'Military', 'value' => 'ATTACK AT DAWN',    'key' => 'ZEBRA'],
                ['label' => 'Decode',   'value' => 'ACDESEEVROWIRDE',    'key' => 'SECRET', 'direction' => 'decrypt'],
            ],
            'classical-ciphers/polybius-square' => [
                ['label' => 'Classic',  'value' => 'HELLO WORLD',       'alphabet' => 'en', 'direction' => 'encrypt', 'delimiter' => 'space'],
                ['label' => 'Military', 'value' => 'ATTACK AT DAWN',    'alphabet' => 'en', 'direction' => 'encrypt', 'delimiter' => 'space'],
                ['label' => 'Decode',   'value' => '23 15 31 31 34',    'alphabet' => 'en', 'direction' => 'decrypt', 'delimiter' => 'space'],
            ],
            'classical-ciphers/affine' => [
                ['label' => 'Classic',  'value' => 'AFFINE CIPHER', 'alphabet' => 'en', 'key' => '5', 'shift' => 8],
                ['label' => 'Military', 'value' => 'ATTACK AT DAWN', 'alphabet' => 'en', 'key' => '7', 'shift' => 3],
                ['label' => 'Decode',   'value' => 'IHHWVC SWFRCP', 'alphabet' => 'en', 'key' => '5', 'shift' => 8, 'direction' => 'decrypt'],
            ],
            'classical-ciphers/hill' => [
                ['label' => 'Classic',  'value' => 'HELP',          'alphabet' => 'en', 'key' => '3 3; 2 5'],
                ['label' => 'Military', 'value' => 'ATTACK AT DAWN', 'alphabet' => 'en', 'key' => '3 3; 2 5'],
                ['label' => 'Decode',   'value' => 'HIAT',          'alphabet' => 'en', 'key' => '3 3; 2 5', 'direction' => 'decrypt'],
            ],
            'classical-ciphers/morse-code' => [
                ['label' => 'SOS',    'value' => 'SOS',       'alphabet' => 'en'],
                ['label' => 'Hello',  'value' => 'HELLO',     'alphabet' => 'en'],
                ['label' => 'Decode', 'value' => '... --- ...',    'alphabet' => 'en', 'direction' => 'decrypt'],
            ],
            default => [],
        };
    }

    /**
     * Возвращает API-действие по slug инструмента.
     */
    public function apiAction(string $toolSlug): ?string
    {
        $canonicalSlug = $this->canonicalSlug($toolSlug);

        return match ($canonicalSlug) {
            'classical-ciphers/affine' => 'affine',
            'classical-ciphers/caesar' => 'caesar',
            'classical-ciphers/atbash' => 'atbash',
            'classical-ciphers/playfair' => 'playfair',
            'classical-ciphers/beaufort' => 'beaufort',
            'classical-ciphers/gronsfeld' => 'gronsfeld',
            'classical-ciphers/vigenere' => 'vigenere',
            'classical-ciphers/vernam' => 'vernam',
            'classical-ciphers/bacon' => 'bacon',
            'classical-ciphers/rot13' => 'rot13',
            'classical-ciphers/a1z26' => 'a1z26',
            'classical-ciphers/rail-fence' => 'rail-fence',
            'classical-ciphers/columnar-transposition' => 'columnar-transposition',
            'classical-ciphers/polybius-square' => 'polybius-square',
            'classical-ciphers/hill' => 'hill',
            'classical-ciphers/morse-code' => null,
            default => null,
        };
    }

    /**
     * Возвращает схему полей настроек для конкретного инструмента.
     *
     * @return array<int, array<string, mixed>>
     */
    public function settings(string $toolSlug): array
    {
        $canonicalSlug = $this->canonicalSlug($toolSlug);

        return match ($canonicalSlug) {
            'classical-ciphers/affine' => $this->affineCipher->getToolSettings(),
            'classical-ciphers/caesar' => $this->caesarCipher->getToolSettings(),
            'classical-ciphers/atbash' => $this->atbashCipher->getToolSettings(),
            'classical-ciphers/playfair' => $this->playfairCipher->getToolSettings(),
            'classical-ciphers/beaufort' => $this->beaufortCipher->getToolSettings(),
            'classical-ciphers/gronsfeld' => $this->gronsfeldCipher->getToolSettings(),
            'classical-ciphers/vigenere' => $this->vigenereCipher->getToolSettings(),
            'classical-ciphers/vernam' => $this->vernamCipher->getToolSettings(),
            'classical-ciphers/bacon' => $this->baconCipher->getToolSettings(),
            'classical-ciphers/rot13' => $this->rot13Cipher->getToolSettings(),
            'classical-ciphers/a1z26' => $this->a1z26Cipher->getToolSettings(),
            'classical-ciphers/rail-fence' => $this->railFenceCipher->getToolSettings(),
            'classical-ciphers/columnar-transposition' => $this->columnarTranspositionCipher->getToolSettings(),
            'classical-ciphers/polybius-square' => $this->polybiusSquareCipher->getToolSettings(),
            'classical-ciphers/hill' => $this->hillCipher->getToolSettings(),
            'classical-ciphers/morse-code' => $this->morseCipher->getToolSettings(),
            default => [],
        };
    }

    /**
     * Возвращает элементы блока доверия для инструмента.
     *
     * @return string[]
     */
    public function trustItems(string $toolSlug, string $calculationMode): array
    {
        return match ($this->canonicalSlug($toolSlug)) {
            'classical-ciphers/affine' => $this->affineCipher->getTrustItems($calculationMode),
            'classical-ciphers/playfair'  => $this->playfairCipher->getTrustItems($calculationMode),
            'classical-ciphers/caesar'    => $this->caesarCipher->getTrustItems($calculationMode),
            'classical-ciphers/atbash'    => $this->atbashCipher->getTrustItems($calculationMode),
            'classical-ciphers/beaufort'  => $this->beaufortCipher->getTrustItems($calculationMode),
            'classical-ciphers/gronsfeld' => $this->gronsfeldCipher->getTrustItems($calculationMode),
            'classical-ciphers/vigenere'  => $this->vigenereCipher->getTrustItems($calculationMode),
            'classical-ciphers/vernam'    => $this->vernamCipher->getTrustItems($calculationMode),
            'classical-ciphers/bacon'     => $this->baconCipher->getTrustItems($calculationMode),
            'classical-ciphers/rot13'     => $this->rot13Cipher->getTrustItems($calculationMode),
            'classical-ciphers/a1z26'     => $this->a1z26Cipher->getTrustItems($calculationMode),
            'classical-ciphers/rail-fence' => $this->railFenceCipher->getTrustItems($calculationMode),
            'classical-ciphers/columnar-transposition' => $this->columnarTranspositionCipher->getTrustItems($calculationMode),
            'classical-ciphers/polybius-square' => $this->polybiusSquareCipher->getTrustItems($calculationMode),
            'classical-ciphers/hill' => $this->hillCipher->getTrustItems($calculationMode),
            'classical-ciphers/morse-code' => $this->morseCipher->getTrustItems($calculationMode),
            'encoding/base64' => [
                trans('BASE64_TRUST_PURPOSE'),
                trans('BASE64_TRUST_USES'),
                trans('CIPHER_TOOL_TRUST_UTF8'),
                trans('CIPHER_TOOL_TRUST_LOCAL'),
            ],
            'encoding/hex' => [
                trans('HEX_TRUST_PURPOSE'),
                trans('HEX_TRUST_DEBUG'),
                trans('CIPHER_TOOL_TRUST_UTF8'),
                trans('CIPHER_TOOL_TRUST_NEVER_LEAVES'),
            ],
            'encoding/url-encode' => [
                trans('URL_TRUST_PURPOSE'),
                trans('URL_TRUST_STANDARD'),
                trans('CIPHER_TOOL_TRUST_UTF8'),
                trans('CIPHER_TOOL_TRUST_LOCAL'),
            ],
            'encoding/binary-converter' => [
                trans('BINARY_TRUST_PURPOSE'),
                trans('BINARY_TRUST_LEVEL'),
                trans('CIPHER_TOOL_TRUST_UTF8'),
                trans('CIPHER_TOOL_TRUST_LOCAL'),
            ],
            'encoding/ascii-converter' => [
                trans('ASCII_TRUST_PURPOSE'),
                trans('ASCII_TRUST_TABLE'),
                trans('ASCII_TRUST_USE'),
                trans('CIPHER_TOOL_TRUST_LOCAL'),
            ],
            'encoding/unicode-converter' => [
                trans('UNICODE_TRUST_PURPOSE'),
                trans('UNICODE_TRUST_EMOJI'),
                trans('UNICODE_TRUST_FORMATS'),
                trans('CIPHER_TOOL_TRUST_LOCAL'),
            ],
            'encoding/jwt-decoder' => [
                trans('JWT_TRUST_PURPOSE'),
                trans('JWT_TRUST_PARTS'),
                trans('JWT_TRUST_KEYLESS'),
                trans('CIPHER_TOOL_TRUST_LOCAL'),
            ],
            default => [
                trans('CIPHER_TOOL_TRUST_LOCAL'),
                trans('CIPHER_TOOL_TRUST_PRIVATE'),
            ],
        };
    }

    /**
     * Возвращает метку поля «ключ» для карточек примеров (переопределяется шифрами, у которых
     * «ключом» в примере является не секретный ключ, а, например, текст-обёртка).
     */
    public function exampleKeyLabel(string $toolSlug): string
    {
        return match ($this->canonicalSlug($toolSlug)) {
            'classical-ciphers/bacon' => trans('BACON_COVER_LABEL_SHORT'),
            default => trans('CIPHER_TOOL_EXAMPLE_KEY_LABEL'),
        };
    }

    /**
     * Возвращает id HTML-элемента, в который нужно подставить значение ключа при нажатии
     * «Use example». По умолчанию это поле ключа шифра; для Bacon — поле cover-текста.
     */
    public function exampleKeyInputId(string $toolSlug): string
    {
        return match ($this->canonicalSlug($toolSlug)) {
            'classical-ciphers/bacon' => 'ciphers-cover',
            default => 'ciphers-key',
        };
    }

    /**
     * Возвращает пояснение, показываемое после дешифрования, или null если не нужно.
     */
    public function decodeNote(string $toolSlug): ?string
    {
        return match ($this->canonicalSlug($toolSlug)) {
            'classical-ciphers/playfair' => trans('PLAYFAIR_DECODE_NOTE'),
            default => null,
        };
    }

    /**
     * Возвращает true, если ключ примеров данного инструмента является матрицей.
     */
    public function exampleKeyIsMatrix(string $toolSlug): bool
    {
        return $this->canonicalSlug($toolSlug) === 'classical-ciphers/hill';
    }

    /**
     * Нормализует slug с учётом алиасов.
     */
    private function canonicalSlug(string $toolSlug): string
    {
        return match ($toolSlug) {
            'classical-ciphers/plejfera', 'classical-ciphers/shifr-plejfera' => 'classical-ciphers/playfair',
            'classical-ciphers/shifr-bofora' => 'classical-ciphers/beaufort',
            'classical-ciphers/shifr-gronsfelda' => 'classical-ciphers/gronsfeld',
            'classical-ciphers/shifr-vizhenera' => 'classical-ciphers/vigenere',
            'classical-ciphers/shifr-vernama' => 'classical-ciphers/vernam',
            'classical-ciphers/shifr-bekona', 'classical-ciphers/shifr-behkona' => 'classical-ciphers/bacon',
            'classical-ciphers/rot-13', 'classical-ciphers/shifr-rot13', 'classical-ciphers/shifr-rot-13' => 'classical-ciphers/rot13',
            'classical-ciphers/shifr-atbash' => 'classical-ciphers/atbash',
            'classical-ciphers/shifr-a1z26' => 'classical-ciphers/a1z26',
            'classical-ciphers/railfence', 'classical-ciphers/shifr-rail-fence' => 'classical-ciphers/rail-fence',
            'classical-ciphers/columnar', 'classical-ciphers/columnar-transposition-cipher', 'classical-ciphers/stolbcovyj-shifr-perestanovki' => 'classical-ciphers/columnar-transposition',
            'classical-ciphers/polybius', 'classical-ciphers/polybius-square-cipher', 'classical-ciphers/kvadrat-polibiya' => 'classical-ciphers/polybius-square',
            'classical-ciphers/affinnyj-shifr', 'classical-ciphers/shifr-affine' => 'classical-ciphers/affine',
            'classical-ciphers/hill-cipher', 'classical-ciphers/shifr-hilla' => 'classical-ciphers/hill',
            'classical-ciphers/morse', 'classical-ciphers/kod-morze', 'classical-ciphers/azbukamorze' => 'classical-ciphers/morse-code',
            default => $toolSlug,
        };
    }
}
