<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * DTO одной кандидат-расшифровки, полученной авто-солвером.
 *
 * В отличие от {@see CipherDetection} (кандидат-*тип* шифра), это уже готовый
 * plaintext, отранжированный по читаемости, — единица вывода флагманского
 * инструмента «вставь → ответ».
 */
final readonly class SolverResult
{
    /**
     * Создаёт экземпляр кандидат-расшифровки.
     *
     * @param string      $toolSlug         Canonical slug инструмента ('classical-ciphers/caesar').
     * @param string      $cipherKey        Ключ перевода названия шифра ('CIPHER_NAME_CAESAR').
     * @param string      $plaintext        Полученная расшифровка.
     * @param string|null $keyLabel         Человекочитаемая метка ключа ('shift=3', 'key=LEMON') или null.
     * @param float       $readability      Оценка читаемости 0.0–1.0.
     * @param int         $readabilityPct   Та же оценка в процентах 0–100 (для UI).
     * @param string|null $viaAction        Через какой brute-force/cracker action получена расшифровка, либо null (прямая).
     * @param string|null $detectedAlphabet Алфавит, по которому оценивалась читаемость ('en' | 'ru' | ...), либо null.
     */
    public function __construct(
        public string $toolSlug,
        public string $cipherKey,
        public string $plaintext,
        public ?string $keyLabel,
        public float $readability,
        public int $readabilityPct,
        public ?string $viaAction,
        public ?string $detectedAlphabet,
    ) {
    }

    /**
     * Представляет расшифровку массивом для JSON-ответа API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tool_slug'         => $this->toolSlug,
            'cipher_key'        => $this->cipherKey,
            'plaintext'         => $this->plaintext,
            'key_label'         => $this->keyLabel,
            'readability'       => round($this->readability, 4),
            'readability_pct'   => $this->readabilityPct,
            'via_action'        => $this->viaAction,
            'detected_alphabet' => $this->detectedAlphabet,
        ];
    }
}
