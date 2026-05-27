<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Единый каталог поддерживаемых алфавитов для шифров.
 */
final class AlphabetCatalog
{
    /** @var array<string, string[]> Набор алфавитов по коду языка. */
    private const array ALPHABETS = [
        'en' => ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'],
        'ru' => ['а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я'],
        'es' => ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'ñ', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'],
        'pt' => ['a', 'á', 'à', 'ã', 'b', 'c', 'ç', 'd', 'e', 'é', 'ê', 'f', 'g', 'h', 'i', 'í', 'j', 'k', 'l', 'm', 'n', 'o', 'ó', 'ô', 'p', 'q', 'r', 's', 't', 'u', 'ú', 'v', 'w', 'x', 'y', 'z'],
        'tr' => ['a', 'b', 'c', 'ç', 'd', 'e', 'f', 'g', 'ğ', 'h', 'ı', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'ö', 'p', 'r', 's', 'ş', 't', 'u', 'ü', 'v', 'y', 'z'],
        'fr' => ['a', 'à', 'â', 'b', 'c', 'ç', 'd', 'e', 'é', 'è', 'ê', 'ë', 'f', 'g', 'h', 'i', 'î', 'ï', 'j', 'k', 'l', 'm', 'n', 'o', 'ô', 'p', 'q', 'r', 's', 't', 'u', 'ù', 'û', 'ü', 'v', 'w', 'x', 'y', 'ÿ', 'z'],
        'de' => ['a', 'ä', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'ö', 'p', 'q', 'r', 's', 'ß', 't', 'u', 'ü', 'v', 'w', 'x', 'y', 'z'],
        'it' => ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'],
    ];

    /**
     * Возвращает список кодов поддерживаемых алфавитов.
     *
     * @return string[]
     */
    public function codes(): array
    {
        return array_keys(self::ALPHABETS);
    }

    /**
     * Возвращает алфавит по коду или fallback-алфавит.
     *
     * @return string[]
     */
    public function alphabet(string $code, string $fallback = 'en'): array
    {
        $normalized = mb_strtolower(trim($code));

        return self::ALPHABETS[$normalized] ?? self::ALPHABETS[$fallback] ?? self::ALPHABETS['en'];
    }

    /**
     * Возвращает все алфавиты каталога.
     *
     * @return array<string, string[]>
     */
    public function all(): array
    {
        return self::ALPHABETS;
    }
}

