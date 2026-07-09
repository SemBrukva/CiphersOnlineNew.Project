<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет примеры для категории cipher-solver и их локализованные переводы.
 *
 * Входная строка (input) универсальна для всех языков, локализуются только
 * подпись (title) и описание (description).
 */
class SeedCipherSolverCategoryExamples extends Migration
{
    /**
     * Создаёт или обновляет примеры и переводы для категории cipher-solver.
     */
    public function up(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['cipher-solver']
        );

        if ($category === false) {
            return;
        }

        $categoryId = (int) $category['id'];
        $now = date('Y-m-d H:i:s');

        foreach ($this->exampleItems() as $sortOrder => $item) {
            $exampleId = $this->upsertExample($categoryId, $sortOrder, $now);

            foreach ($item['translations'] as $language => $translation) {
                $this->upsertExampleTranslation(
                    $exampleId,
                    $language,
                    (string) $translation['title'],
                    (string) $item['input'],
                    (string) $translation['description'],
                    $now
                );
            }
        }
    }

    /**
     * Удаляет примеры, добавленные этой миграцией, для категории cipher-solver.
     */
    public function down(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['cipher-solver']
        );

        if ($category === false) {
            return;
        }

        $categoryId = (int) $category['id'];
        $sortOrders = array_keys($this->exampleItems());

        if ($sortOrders === []) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($sortOrders), '?'));
        $bindings = array_merge([$categoryId], $sortOrders);

        $rows = $this->db->fetchAll(
            'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_EXAMPLES
            . ' WHERE category_id = ? AND sort_order IN (' . $placeholders . ')',
            $bindings
        );

        foreach ($rows as $row) {
            $exampleId = (int) $row['id'];
            $this->db->execute(
                'DELETE FROM ' . Tables::CIPHERS_CATEGORIES_EXAMPLES_TRANSLATIONS . ' WHERE example_id = ?',
                [$exampleId]
            );
            $this->db->execute(
                'DELETE FROM ' . Tables::CIPHERS_CATEGORIES_EXAMPLES . ' WHERE id = ?',
                [$exampleId]
            );
        }
    }

    /**
     * Создаёт или обновляет запись примера категории.
     */
    private function upsertExample(int $categoryId, int $sortOrder, string $now): int
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_EXAMPLES
            . ' WHERE category_id = ? AND sort_order = ? LIMIT 1',
            [$categoryId, $sortOrder]
        );

        if ($existing === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_EXAMPLES
                . ' (category_id, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                [$categoryId, $sortOrder, 1, $now, $now]
            );
        }

        $exampleId = (int) $existing['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_CATEGORIES_EXAMPLES . ' SET published = ?, updated_at = ? WHERE id = ?',
            [1, $now, $exampleId]
        );

        return $exampleId;
    }

    /**
     * Создаёт или обновляет перевод примера.
     */
    private function upsertExampleTranslation(int $exampleId, string $language, string $title, string $input, string $description, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_EXAMPLES_TRANSLATIONS
            . ' WHERE example_id = ? AND language = ? LIMIT 1',
            [$exampleId, $language]
        );

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_EXAMPLES_TRANSLATIONS
                . ' (example_id, language, title, input, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$exampleId, $language, $title, $input, $description, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_CATEGORIES_EXAMPLES_TRANSLATIONS
            . ' SET title = ?, input = ?, description = ?, updated_at = ? WHERE id = ?',
            [$title, $input, $description, $now, (int) $existing['id']]
        );
    }

    /**
     * Возвращает примеры с универсальным input и локализованными title/description.
     *
     * @return array<int, array{input:string, translations:array<string, array{title:string, description:string}>}>
     */
    private function exampleItems(): array
    {
        return [
            10 => [
                'input' => 'KHOOR ZRUOG WKLV LV D WHVW PHVVDJH IRU FLSKHU GHWHFWLRQ',
                'translations' => [
                    'en' => ['title' => 'Caesar cipher', 'description' => 'Each letter is shifted by a fixed number of positions in the alphabet.'],
                    'ru' => ['title' => 'Шифр Цезаря', 'description' => 'Каждая буква сдвинута на фиксированное число позиций в алфавите.'],
                    'de' => ['title' => 'Caesar-Chiffre', 'description' => 'Jeder Buchstabe ist um eine feste Anzahl von Positionen im Alphabet verschoben.'],
                    'es' => ['title' => 'Cifrado César', 'description' => 'Cada letra se desplaza un número fijo de posiciones en el alfabeto.'],
                    'fr' => ['title' => 'Chiffre de César', 'description' => 'Chaque lettre est décalée d’un nombre fixe de positions dans l’alphabet.'],
                    'it' => ['title' => 'Cifrario di Cesare', 'description' => 'Ogni lettera è spostata di un numero fisso di posizioni nell’alfabeto.'],
                    'pt' => ['title' => 'Cifra de César', 'description' => 'Cada letra é deslocada por um número fixo de posições no alfabeto.'],
                    'tr' => ['title' => 'Sezar şifresi', 'description' => 'Her harf alfabede sabit sayıda konum kaydırılır.'],
                ],
            ],
            20 => [
                'input' => 'VGhlIHF1aWNrIGJyb3duIGZveCBqdW1wcyBvdmVyIHRoZSBsYXp5IGRvZw==',
                'translations' => [
                    'en' => ['title' => 'Base64', 'description' => 'Binary data represented as ASCII text — common in APIs and HTTP.'],
                    'ru' => ['title' => 'Base64', 'description' => 'Бинарные данные в виде ASCII-текста — часто встречается в API и HTTP.'],
                    'de' => ['title' => 'Base64', 'description' => 'Binärdaten als ASCII-Text dargestellt — häufig in APIs und HTTP.'],
                    'es' => ['title' => 'Base64', 'description' => 'Datos binarios representados como texto ASCII, común en APIs y HTTP.'],
                    'fr' => ['title' => 'Base64', 'description' => 'Données binaires représentées en texte ASCII — courant dans les API et HTTP.'],
                    'it' => ['title' => 'Base64', 'description' => 'Dati binari rappresentati come testo ASCII — comune in API e HTTP.'],
                    'pt' => ['title' => 'Base64', 'description' => 'Dados binários representados como texto ASCII — comum em APIs e HTTP.'],
                    'tr' => ['title' => 'Base64', 'description' => 'İkili verinin ASCII metni olarak gösterimi — API ve HTTP’de yaygın.'],
                ],
            ],
        ];
    }
}
