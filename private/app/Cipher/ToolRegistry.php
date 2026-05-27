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
        private BeaufortCipherService $beaufortCipher,
        private CaesarCipherService $caesarCipher,
        private GronsfeldCipherService $gronsfeldCipher,
        private PlayfairCipherService $playfairCipher,
        private VigenereCipherService $vigenereCipher,
        private VernamCipherService $vernamCipher,
        private BaconCipherService $baconCipher
    ) {
    }

    /**
     * Возвращает примерные значения (chips) для инструмента.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function exampleChips(string $toolSlug): array
    {
        $canonicalSlug = $this->canonicalSlug($toolSlug);

        return match ($canonicalSlug) {
            'encoding/base64' => [
                ['label' => 'JSON', 'value' => '{"id":42,"role":"admin","active":true}'],
                ['label' => 'Unicode', 'value' => 'Привет мир 👋'],
                ['label' => 'Header', 'value' => 'Authorization: Basic dXNlcjpwYXNzd29yZA=='],
            ],
            'encoding/hex' => [
                ['label' => 'JSON', 'value' => '{"id":42,"role":"admin","active":true}'],
                ['label' => 'Unicode', 'value' => 'Привет мир 👋'],
                ['label' => 'Hex', 'value' => '48 65 6c 6c 6f 2c 20 77 6f 72 6c 64 21'],
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
                ['label' => 'EN', 'value' => 'HELLO WORLD'],
                ['label' => 'RU', 'value' => 'ПРИВЕТ МИР'],
                ['label' => 'ES', 'value' => 'HOLA MUNDO'],
            ],
            'classical-ciphers/beaufort' => [
                ['label' => 'EN', 'value' => 'DEFEND THE EAST WALL'],
                ['label' => 'RU', 'value' => 'ЗАЩИЩАЙ ВОСТОЧНУЮ СТЕНУ'],
                ['label' => 'ES', 'value' => 'DEFIENDE EL MURO ESTE'],
            ],
            'classical-ciphers/gronsfeld' => [
                ['label' => 'EN', 'value' => 'HELLO WORLD'],
                ['label' => 'RU', 'value' => 'ПРИВЕТ МИР'],
                ['label' => 'ES', 'value' => 'HOLA MUNDO'],
            ],
            'classical-ciphers/vigenere' => [
                ['label' => 'EN', 'value' => 'ATTACK AT DAWN'],
                ['label' => 'RU', 'value' => 'ПРИВЕТ МИР'],
                ['label' => 'ES', 'value' => 'ATAQUE AL AMANECER'],
            ],
            'classical-ciphers/vernam' => [
                ['label' => 'EN', 'value' => 'HELLO WORLD'],
                ['label' => 'RU', 'value' => 'ПРИВЕТ МИР'],
                ['label' => 'ES', 'value' => 'HOLA MUNDO'],
            ],
            'classical-ciphers/bacon' => [
                ['label' => 'EN', 'value' => 'HELLO WORLD'],
                ['label' => 'RU', 'value' => 'ПРИВЕТ МИР'],
                ['label' => 'AB', 'value' => 'AABBB AABAA'],
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
            'classical-ciphers/caesar' => 'caesar',
            'classical-ciphers/playfair' => 'playfair',
            'classical-ciphers/beaufort' => 'beaufort',
            'classical-ciphers/gronsfeld' => 'gronsfeld',
            'classical-ciphers/vigenere' => 'vigenere',
            'classical-ciphers/vernam' => 'vernam',
            'classical-ciphers/bacon' => 'bacon',
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
            'classical-ciphers/caesar' => $this->caesarCipher->getToolSettings(),
            'classical-ciphers/playfair' => $this->playfairCipher->getToolSettings(),
            'classical-ciphers/beaufort' => $this->beaufortCipher->getToolSettings(),
            'classical-ciphers/gronsfeld' => $this->gronsfeldCipher->getToolSettings(),
            'classical-ciphers/vigenere' => $this->vigenereCipher->getToolSettings(),
            'classical-ciphers/vernam' => $this->vernamCipher->getToolSettings(),
            'classical-ciphers/bacon' => $this->baconCipher->getToolSettings(),
            default => [],
        };
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
            default => $toolSlug,
        };
    }
}
