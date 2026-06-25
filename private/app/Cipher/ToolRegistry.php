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
        private PortaCipherService $portaCipher,
        private AutokeyCipherService $autokeyCipher,
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
        private MorseCipherService $morseCipher,
        private FrequencyAnalysisService $frequencyAnalysis,
        private CaesarBruteForceService $caesarBruteForce,
        private LetterFrequencyService $letterFrequency,
        private NumbersToLettersService $numbersToLetters,
        private HtmlEncodeCipherService $htmlEncode,
        private JsonFormatterCipherService $jsonFormatter,
        private TimestampConverterCipherService $timestampConverter,
        private SimpleSubstitutionCipherService $simpleSubstitution,
        private XorCipherService $xorCipher,
        private VigenereCrackerService $vigenereCracker,
        private AffineBruteForceService $affineBruteForce,
        private BifidCipherService $bifidCipher,
        private TrifidCipherService $trifidCipher,
        private AlbertiCipherService $albertiCipher,
        private EnigmaCipherService $enigmaCipher,
        private CipherIdentifierService $cipherIdentifier
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
            'encoding/html-encode' => [
                ['label' => 'Tags',    'value' => '<h1>Hello & "World"</h1>'],
                ['label' => 'Script',  'value' => '<script>alert(\'xss\')</script>'],
                ['label' => 'Encoded', 'value' => '&lt;p&gt;Caf&eacute; &amp; na&iuml;ve&lt;/p&gt;', 'direction' => 'decrypt'],
            ],
            'encoding/json-formatter' => [
                ['label' => 'Object',  'value' => '{"name":"Alice","age":30,"active":true}'],
                ['label' => 'Array',   'value' => '[{"id":1,"role":"admin"},{"id":2,"role":"user"}]'],
                ['label' => 'Minify',  'value' => "{\n  \"key\": \"value\",\n  \"count\": 42\n}", 'direction' => 'decrypt'],
            ],
            'encoding/timestamp-converter' => [
                ['label' => 'Epoch',    'value' => '0'],
                ['label' => 'Y2K',      'value' => '946684800'],
                ['label' => '2001-9-11','value' => '1000166400'],
                ['label' => 'ISO date', 'value' => '2024-01-15T12:00:00Z', 'direction' => 'decrypt'],
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
            'classical-ciphers/porta' => [
                ['label' => 'Classic',  'value' => 'HELLO WORLD',          'key' => 'PORTA'],
                ['label' => 'Military', 'value' => 'DEFEND THE EAST WALL', 'key' => 'SECRET'],
                ['label' => 'Decode',   'value' => 'OYTUB CHJUQ',          'key' => 'PORTA', 'direction' => 'decrypt'],
            ],
            'classical-ciphers/autokey' => [
                ['label' => 'Classic',  'value' => 'ATTACK AT DAWN',       'alphabet' => 'en', 'key' => 'QUEENLY'],
                ['label' => 'Military', 'value' => 'DEFEND THE EAST WALL', 'alphabet' => 'en', 'key' => 'FORT'],
                ['label' => 'Decode',   'value' => 'QNXEPV YT WTWP',       'alphabet' => 'en', 'key' => 'QUEENLY', 'direction' => 'decrypt'],
            ],
            'classical-ciphers/bifid' => [
                ['label' => 'Classic',  'value' => 'HELLO',          'alphabet' => 'en', 'key' => 'KEYWORD'],
                ['label' => 'Military', 'value' => 'ATTACK AT DAWN', 'alphabet' => 'en', 'key' => 'PLAYFAIR'],
                ['label' => 'Decode',   'value' => 'FHYCZ',          'alphabet' => 'en', 'key' => 'KEYWORD', 'direction' => 'decrypt'],
            ],
            'classical-ciphers/trifid' => [
                ['label' => 'Classic',  'value' => 'HELLO',          'alphabet' => 'en', 'key' => 'KEYWORD'],
                ['label' => 'Military', 'value' => 'ATTACK AT DAWN', 'alphabet' => 'en', 'key' => 'PLAYFAIR'],
                ['label' => 'Decode',   'value' => 'FFOF1',          'alphabet' => 'en', 'key' => 'KEYWORD', 'direction' => 'decrypt'],
                ['label' => 'Deutsch',   'value' => 'BERLIN',         'alphabet' => 'de', 'key' => 'GEHEIMNIS'],
                ['label' => 'Türkçe',    'value' => 'ISTANBUL',       'alphabet' => 'tr', 'key' => 'ANAHTAR'],
                ['label' => 'Português', 'value' => 'PORTO',          'alphabet' => 'pt', 'key' => 'SEGREDO'],
                ['label' => 'Français',  'value' => 'BONJOUR',        'alphabet' => 'fr', 'key' => 'SECRET'],
            ],
            'classical-ciphers/alberti' => [
                ['label' => 'Classic',  'value' => 'HELLO WORLD',    'key' => 'ALBERTI', 'alberti_index' => 'A'],
                ['label' => 'Military', 'value' => 'ATTACK AT DAWN', 'key' => 'ZEBRAS',  'alberti_index' => 'A'],
                ['label' => 'Decode',   'value' => 'CRHHM WMPHE',    'key' => 'ALBERTI', 'alberti_index' => 'A', 'direction' => 'decrypt'],
            ],
            'classical-ciphers/enigma' => [
                [
                    'label' => 'Default',  'value' => 'HELLO WORLD',
                    'enigma_reflector' => 'B',
                    'enigma_rotor_left' => 'I', 'enigma_rotor_middle' => 'II', 'enigma_rotor_right' => 'III',
                    'enigma_ring_left' => 'A', 'enigma_ring_middle' => 'A', 'enigma_ring_right' => 'A',
                    'enigma_pos_left' => 'A', 'enigma_pos_middle' => 'A', 'enigma_pos_right' => 'A',
                    'enigma_plugboard' => '',
                ],
                [
                    'label' => 'Plugboard', 'value' => 'ATTACK AT DAWN',
                    'enigma_reflector' => 'B',
                    'enigma_rotor_left' => 'I', 'enigma_rotor_middle' => 'II', 'enigma_rotor_right' => 'III',
                    'enigma_ring_left' => 'A', 'enigma_ring_middle' => 'A', 'enigma_ring_right' => 'A',
                    'enigma_pos_left' => 'M', 'enigma_pos_middle' => 'C', 'enigma_pos_right' => 'K',
                    'enigma_plugboard' => 'AB CD EF',
                ],
                [
                    'label' => 'Decrypt', 'value' => 'ILBDA AMTAZ', 'direction' => 'decrypt',
                    'enigma_reflector' => 'B',
                    'enigma_rotor_left' => 'I', 'enigma_rotor_middle' => 'II', 'enigma_rotor_right' => 'III',
                    'enigma_ring_left' => 'A', 'enigma_ring_middle' => 'A', 'enigma_ring_right' => 'A',
                    'enigma_pos_left' => 'A', 'enigma_pos_middle' => 'A', 'enigma_pos_right' => 'A',
                    'enigma_plugboard' => '',
                ],
                [
                    'label' => 'Reflector C', 'value' => 'CRYPTO',
                    'enigma_reflector' => 'C',
                    'enigma_rotor_left' => 'IV', 'enigma_rotor_middle' => 'V', 'enigma_rotor_right' => 'I',
                    'enigma_ring_left' => 'B', 'enigma_ring_middle' => 'C', 'enigma_ring_right' => 'D',
                    'enigma_pos_left' => 'X', 'enigma_pos_middle' => 'Y', 'enigma_pos_right' => 'Z',
                    'enigma_plugboard' => 'AT BS DE FG IJ',
                ],
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
            'codes-and-alphabets/bacon' => [
                ['label' => 'Classic',  'value' => 'HELLO WORLD',    'alphabet' => 'en'],
                ['label' => 'Military', 'value' => 'ATTACK AT DAWN', 'alphabet' => 'en'],
                ['label' => 'Stego',    'value' => 'HELLO',          'alphabet' => 'en', 'key' => 'The quick brown fox jumps over the lazy dog'],
            ],
            'codes-and-alphabets/a1z26' => [
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
            'codes-and-alphabets/polybius-square' => [
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
            'classical-ciphers/simple-substitution' => [
                ['label' => 'Classic',  'value' => 'HELLO WORLD',    'alphabet' => 'en', 'key' => 'QWERTYUIOPASDFGHJKLZXCVBNM'],
                ['label' => 'Military', 'value' => 'ATTACK AT DAWN', 'alphabet' => 'en', 'key' => 'QWERTYUIOPASDFGHJKLZXCVBNM'],
                ['label' => 'Decode',   'value' => 'ITSSG VGKSR',    'alphabet' => 'en', 'key' => 'QWERTYUIOPASDFGHJKLZXCVBNM', 'direction' => 'decrypt'],
            ],
            'classical-ciphers/xor-cipher' => [
                ['label' => 'Classic',  'value' => 'HELLO',          'key' => 'KEY'],
                ['label' => 'Military', 'value' => 'ATTACK AT DAWN', 'key' => 'SECRET'],
                ['label' => 'Decode',   'value' => '030015070A',     'key' => 'KEY', 'direction' => 'decrypt'],
                ['label' => 'Hex Key',  'value' => 'HELLO',          'key' => '42',  'key_format' => 'hex'],
            ],
            'codes-and-alphabets/morse-code' => [
                ['label' => 'SOS',    'value' => 'SOS',       'alphabet' => 'en'],
                ['label' => 'Hello',  'value' => 'HELLO',     'alphabet' => 'en'],
                ['label' => 'Decode', 'value' => '... --- ...',    'alphabet' => 'en', 'direction' => 'decrypt'],
            ],
            'text-analysis/frequency-analysis' => [
                ['label' => 'English', 'value' => 'The quick brown fox jumps over the lazy dog', 'alphabet' => 'en'],
                ['label' => 'Caesar',  'value' => 'KHOOR ZRUOG',                                 'alphabet' => 'en'],
                ['label' => 'Hamlet',  'value' => 'To be or not to be that is the question',     'alphabet' => 'en'],
            ],
            'text-analysis/caesar-brute-force' => [
                ['label' => 'ROT-13',   'value' => 'URYYB JBEYQ',       'alphabet' => 'en'],
                ['label' => 'Shift 3',  'value' => 'KHOOR ZRUOG',       'alphabet' => 'en'],
                ['label' => 'Shift 7',  'value' => 'HAAHJR HA KHDU',    'alphabet' => 'en'],
            ],
            'text-analysis/cipher-identifier' => [
                ['label' => 'Morse',    'value' => '.... . .-.. .-.. --- / .-- --- .-. .-.. -..'],
                ['label' => 'Base64',   'value' => 'SGVsbG8gV29ybGQh'],
                ['label' => 'Caesar',   'value' => 'KHOOR ZRUOG WKLV LV D WHVW RI WKH FDHVDU FLSKHU'],
                ['label' => 'Vigenere', 'value' => 'SX UKW RRI ZOWR YJ RSQCC MR GEQ DLC GSPCX MP XGWIQ SX UKW RRI YQI MP AGCHMW MR G'],
            ],
            'text-analysis/vigenere-cracker' => [
                ['label' => 'Key KEY',    'value' => 'SX UKW RRI ZOWR YJ RSQCC MR GEQ DLC GSPCX MP XGWIQ SX UKW RRI YQI MP AGCHMW MR GEQ DLC KKC YJ DYSJSWFXIQC MR GEQ DLC OTMML MP FCVMCP MR GEQ DLC OTMML MP MLMVCNYJSXW',   'alphabet' => 'en'],
                ['label' => 'Key LEMON',  'value' => 'QSGF FNSDS NYH ESIPR KSNCW MUB ZYD TNELQFF MVAITSX RCEEL AB GSME QBYXUBRYX M BRH RMHVZR OCANIUJRO MZ ZVMIDHL LRP RROMOOGPH FC GSI BFBASEWGTSZ HULX MZY XIZ OEP GDSNEIP SDFEX', 'alphabet' => 'en'],
                ['label' => 'Key SECRET', 'value' => 'LS DV SK FSV KS UW XJRX BK XJV UNWWVZSG OLGKLXJ XKJ RHTPGI MG LLG DMGV XQ JYYXIT KLX KPKEKL SRF RVKGAU FJ HMXTRKXGYU WSKLYPV SK LS VROX SVOJ EZSMPJX T KIC FJ MJSWSPXK', 'alphabet' => 'en'],
            ],
            'text-analysis/affine-brute-force' => [
                ['label' => 'Cryptography',  'value' => 'Ihhwvc swfrcpu cvspyfz cisr lczzcp owzr zoa veqcpws gcyu ivx pcjcil livmeimc fizzcpvu evxcp ivilyuwu',                'alphabet' => 'en'],
                ['label' => 'Cryptanalysis', 'value' => 'Ksngf mxsrf dggdrvz gsp fufsp udchy vfp edhs nqghc gaf ecdhq gfig fjfstfz hq sfdydkcf mxsj mxs dqdcpzhz',              'alphabet' => 'en'],
                ['label' => 'Pangram',       'value' => 'Fjk gwaci lxyeh byv rwqpo ynkx fjk zudm tys ejazk xkutahs czuooacuz zafkxufwxk ulywf uhcakhf jattkh cytko',            'alphabet' => 'en'],
            ],
            'text-analysis/letter-frequency' => [
                ['label' => 'English', 'value' => 'The quick brown fox jumps over the lazy dog', 'alphabet' => 'en'],
                ['label' => 'Caesar',  'value' => 'KHOOR ZRUOG',                                 'alphabet' => 'en'],
                ['label' => 'Hamlet',  'value' => 'To be or not to be that is the question',     'alphabet' => 'en'],
            ],
            'codes-and-alphabets/numbers-to-letters' => [
                ['label' => 'A1Z26',    'value' => '8 5 12 12 15',      'direction' => 'encrypt', 'encoding' => 'positional-1', 'delimiter' => 'space', 'alphabet' => 'en'],
                ['label' => 'ASCII',    'value' => '72 101 108 108 111', 'direction' => 'encrypt', 'encoding' => 'ascii',        'delimiter' => 'space'],
                ['label' => 'Binary',   'value' => '01001000 01101001',  'direction' => 'encrypt', 'encoding' => 'binary',       'delimiter' => 'space'],
                ['label' => 'Letters→', 'value' => 'Hello World',        'direction' => 'decrypt', 'encoding' => 'positional-1', 'delimiter' => 'space', 'alphabet' => 'en'],
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
            'classical-ciphers/porta' => 'porta',
            'classical-ciphers/autokey' => 'autokey',
            'classical-ciphers/bifid'    => 'bifid',
            'classical-ciphers/trifid'   => 'trifid',
            'classical-ciphers/alberti'  => 'alberti',
            'classical-ciphers/enigma'   => 'enigma',
            'classical-ciphers/gronsfeld' => 'gronsfeld',
            'classical-ciphers/vigenere' => 'vigenere',
            'classical-ciphers/vernam' => 'vernam',
            'codes-and-alphabets/bacon' => 'bacon',
            'classical-ciphers/rot13' => 'rot13',
            'codes-and-alphabets/a1z26' => 'a1z26',
            'classical-ciphers/rail-fence' => 'rail-fence',
            'classical-ciphers/columnar-transposition' => 'columnar-transposition',
            'codes-and-alphabets/polybius-square' => 'polybius-square',
            'classical-ciphers/hill' => 'hill',
            'classical-ciphers/simple-substitution' => 'simple-substitution',
            'classical-ciphers/xor-cipher' => 'xor',
            'codes-and-alphabets/morse-code' => null,
            'codes-and-alphabets/numbers-to-letters' => null,
            'encoding/html-encode' => null,
            'encoding/json-formatter' => null,
            'encoding/timestamp-converter' => null,
            'text-analysis/frequency-analysis' => null,
            'text-analysis/caesar-brute-force' => 'caesar-brute-force',
            'text-analysis/affine-brute-force' => 'affine-brute-force',
            'text-analysis/vigenere-cracker'   => 'vigenere-cracker',
            'text-analysis/cipher-identifier'  => 'cipher-identifier',
            'text-analysis/letter-frequency' => null,
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
            'classical-ciphers/porta' => $this->portaCipher->getToolSettings(),
            'classical-ciphers/autokey' => $this->autokeyCipher->getToolSettings(),
            'classical-ciphers/bifid'    => $this->bifidCipher->getToolSettings(),
            'classical-ciphers/trifid'   => $this->trifidCipher->getToolSettings(),
            'classical-ciphers/alberti'  => $this->albertiCipher->getToolSettings(),
            'classical-ciphers/enigma'   => $this->enigmaCipher->getToolSettings(),
            'classical-ciphers/gronsfeld' => $this->gronsfeldCipher->getToolSettings(),
            'classical-ciphers/vigenere' => $this->vigenereCipher->getToolSettings(),
            'classical-ciphers/vernam' => $this->vernamCipher->getToolSettings(),
            'codes-and-alphabets/bacon' => $this->baconCipher->getToolSettings(),
            'classical-ciphers/rot13' => $this->rot13Cipher->getToolSettings(),
            'codes-and-alphabets/a1z26' => $this->a1z26Cipher->getToolSettings(),
            'classical-ciphers/rail-fence' => $this->railFenceCipher->getToolSettings(),
            'classical-ciphers/columnar-transposition' => $this->columnarTranspositionCipher->getToolSettings(),
            'codes-and-alphabets/polybius-square' => $this->polybiusSquareCipher->getToolSettings(),
            'classical-ciphers/hill' => $this->hillCipher->getToolSettings(),
            'classical-ciphers/simple-substitution' => $this->simpleSubstitution->getToolSettings(),
            'classical-ciphers/xor-cipher' => $this->xorCipher->getToolSettings(),
            'codes-and-alphabets/morse-code' => $this->morseCipher->getToolSettings(),
            'codes-and-alphabets/numbers-to-letters' => $this->numbersToLetters->getToolSettings(),
            'encoding/html-encode' => $this->htmlEncode->getToolSettings(),
            'encoding/json-formatter' => $this->jsonFormatter->getToolSettings(),
            'encoding/timestamp-converter' => $this->timestampConverter->getToolSettings(),
            'text-analysis/frequency-analysis' => $this->frequencyAnalysis->getToolSettings(),
            'text-analysis/caesar-brute-force'  => $this->caesarBruteForce->getToolSettings(),
            'text-analysis/affine-brute-force'  => $this->affineBruteForce->getToolSettings(),
            'text-analysis/vigenere-cracker'    => $this->vigenereCracker->getToolSettings(),
            'text-analysis/letter-frequency'    => $this->letterFrequency->getToolSettings(),
            'text-analysis/cipher-identifier'   => $this->cipherIdentifier->getToolSettings(),
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
            'classical-ciphers/porta'     => $this->portaCipher->getTrustItems($calculationMode),
            'classical-ciphers/autokey'   => $this->autokeyCipher->getTrustItems($calculationMode),
            'classical-ciphers/bifid'     => $this->bifidCipher->getTrustItems($calculationMode),
            'classical-ciphers/trifid'    => $this->trifidCipher->getTrustItems($calculationMode),
            'classical-ciphers/alberti'   => $this->albertiCipher->getTrustItems($calculationMode),
            'classical-ciphers/enigma'    => $this->enigmaCipher->getTrustItems($calculationMode),
            'classical-ciphers/gronsfeld' => $this->gronsfeldCipher->getTrustItems($calculationMode),
            'classical-ciphers/vigenere'  => $this->vigenereCipher->getTrustItems($calculationMode),
            'classical-ciphers/vernam'    => $this->vernamCipher->getTrustItems($calculationMode),
            'codes-and-alphabets/bacon'   => $this->baconCipher->getTrustItems($calculationMode),
            'classical-ciphers/rot13'     => $this->rot13Cipher->getTrustItems($calculationMode),
            'codes-and-alphabets/a1z26'   => $this->a1z26Cipher->getTrustItems($calculationMode),
            'classical-ciphers/rail-fence' => $this->railFenceCipher->getTrustItems($calculationMode),
            'classical-ciphers/columnar-transposition' => $this->columnarTranspositionCipher->getTrustItems($calculationMode),
            'codes-and-alphabets/polybius-square' => $this->polybiusSquareCipher->getTrustItems($calculationMode),
            'classical-ciphers/hill' => $this->hillCipher->getTrustItems($calculationMode),
            'classical-ciphers/simple-substitution' => $this->simpleSubstitution->getTrustItems($calculationMode),
            'classical-ciphers/xor-cipher' => $this->xorCipher->getTrustItems($calculationMode),
            'codes-and-alphabets/morse-code' => $this->morseCipher->getTrustItems($calculationMode),
            'codes-and-alphabets/numbers-to-letters' => $this->numbersToLetters->getTrustItems($calculationMode),
            'text-analysis/frequency-analysis' => $this->frequencyAnalysis->getTrustItems($calculationMode),
            'text-analysis/caesar-brute-force' => $this->caesarBruteForce->getTrustItems($calculationMode),
            'text-analysis/affine-brute-force'  => $this->affineBruteForce->getTrustItems($calculationMode),
            'text-analysis/vigenere-cracker'    => $this->vigenereCracker->getTrustItems($calculationMode),
            'text-analysis/letter-frequency'    => $this->letterFrequency->getTrustItems($calculationMode),
            'text-analysis/cipher-identifier'   => $this->cipherIdentifier->getTrustItems($calculationMode),
            'encoding/html-encode' => $this->htmlEncode->getTrustItems($calculationMode),
            'encoding/json-formatter' => $this->jsonFormatter->getTrustItems($calculationMode),
            'encoding/timestamp-converter' => $this->timestampConverter->getTrustItems($calculationMode),
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
            'codes-and-alphabets/bacon' => trans('BACON_COVER_LABEL_SHORT'),
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
            'codes-and-alphabets/bacon' => 'ciphers-cover',
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
            'classical-ciphers/porta-cipher', 'classical-ciphers/shifr-porta' => 'classical-ciphers/porta',
            'classical-ciphers/autokey-cipher', 'classical-ciphers/shifr-autokey' => 'classical-ciphers/autokey',
            'classical-ciphers/bifid-cipher', 'classical-ciphers/shifr-bifida' => 'classical-ciphers/bifid',
            'classical-ciphers/trifid-cipher', 'classical-ciphers/shifr-trifida' => 'classical-ciphers/trifid',
            'classical-ciphers/alberti-cipher', 'classical-ciphers/shifr-alberti', 'classical-ciphers/disk-alberti' => 'classical-ciphers/alberti',
            'classical-ciphers/enigma-machine', 'classical-ciphers/enigma-cipher', 'classical-ciphers/shifr-enigma', 'classical-ciphers/mashina-enigma' => 'classical-ciphers/enigma',
            'classical-ciphers/shifr-gronsfelda' => 'classical-ciphers/gronsfeld',
            'classical-ciphers/shifr-vizhenera' => 'classical-ciphers/vigenere',
            'classical-ciphers/shifr-vernama' => 'classical-ciphers/vernam',
            'classical-ciphers/shifr-bekona', 'classical-ciphers/shifr-behkona',
            'classical-ciphers/bacon' => 'codes-and-alphabets/bacon',
            'classical-ciphers/rot-13', 'classical-ciphers/shifr-rot13', 'classical-ciphers/shifr-rot-13' => 'classical-ciphers/rot13',
            'classical-ciphers/shifr-atbash' => 'classical-ciphers/atbash',
            'classical-ciphers/shifr-a1z26', 'classical-ciphers/a1z26' => 'codes-and-alphabets/a1z26',
            'classical-ciphers/railfence', 'classical-ciphers/shifr-rail-fence' => 'classical-ciphers/rail-fence',
            'classical-ciphers/columnar', 'classical-ciphers/columnar-transposition-cipher', 'classical-ciphers/stolbcovyj-shifr-perestanovki' => 'classical-ciphers/columnar-transposition',
            'classical-ciphers/polybius', 'classical-ciphers/polybius-square-cipher', 'classical-ciphers/kvadrat-polibiya',
            'classical-ciphers/polybius-square' => 'codes-and-alphabets/polybius-square',
            'classical-ciphers/affinnyj-shifr', 'classical-ciphers/shifr-affine' => 'classical-ciphers/affine',
            'classical-ciphers/hill-cipher', 'classical-ciphers/shifr-hilla' => 'classical-ciphers/hill',
            'classical-ciphers/morse', 'classical-ciphers/kod-morze', 'classical-ciphers/azbukamorze',
            'classical-ciphers/morse-code' => 'codes-and-alphabets/morse-code',
            default => $toolSlug,
        };
    }
}
