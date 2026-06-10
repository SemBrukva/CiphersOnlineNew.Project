<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Переносит нотационные шифры в категорию кодов и алфавитов.
 */
class MoveNotationCiphersToCodesAndAlphabets extends Migration
{
    /**
     * Переносит A1Z26, квадрат Полибия и шифр Бэкона в Codes & Alphabets.
     */
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->db->transaction(function () use ($now): void {
            $category = $this->db->fetch(
                'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
                ['codes-and-alphabets']
            );

            if ($category === false) {
                return;
            }

            $categoryId = (int) $category['id'];

            foreach ($this->cipherSortOrders() as $alias => $sortOrder) {
                $this->moveCipher($alias, $categoryId, $sortOrder, $now);
            }

            foreach ($this->translations() as $language => $translation) {
                $this->updateCategoryTranslation($categoryId, $language, $translation, $now);
            }

            foreach ($this->redirects() as [$fromPath, $toPath]) {
                $this->upsertRedirect($fromPath, $toPath, $now);
            }
        });
    }

    /**
     * Возвращает перенесённые шифры в категорию классических шифров.
     */
    public function down(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->db->transaction(function () use ($now): void {
            $category = $this->db->fetch(
                'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
                ['classical-ciphers']
            );

            if ($category !== false) {
                $categoryId = (int) $category['id'];

                foreach ($this->originalCipherSortOrders() as $alias => $sortOrder) {
                    $this->moveCipher($alias, $categoryId, $sortOrder, $now);
                }
            }

            foreach ($this->redirects() as [$fromPath]) {
                $this->db->execute('DELETE FROM ' . Tables::REDIRECTS . ' WHERE from_path = ?', [$fromPath]);
            }
        });
    }

    /**
     * Перемещает шифр в указанную категорию.
     */
    private function moveCipher(string $alias, int $categoryId, int $sortOrder, string $now): void
    {
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS . ' SET category_id = ?, sort_order = ?, updated_at = ? WHERE alias = ?',
            [$categoryId, $sortOrder, $now, $alias]
        );
    }

    /**
     * Обновляет перевод категории.
     *
     * @param array{name: string, name_short: string, description: string, meta_title: string, meta_description: string} $translation Данные перевода.
     */
    private function updateCategoryTranslation(int $categoryId, string $language, array $translation, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORY_TRANSLATIONS
            . ' WHERE category_id = ? AND language = ? LIMIT 1',
            [$categoryId, $language]
        );

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHER_CATEGORY_TRANSLATIONS
                . ' (category_id, language, name, name_short, description, meta_title, meta_description, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $categoryId,
                    $language,
                    $translation['name'],
                    $translation['name_short'],
                    $translation['description'],
                    $translation['meta_title'],
                    $translation['meta_description'],
                    $now,
                    $now,
                ]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHER_CATEGORY_TRANSLATIONS
            . ' SET name = ?, name_short = ?, description = ?, meta_title = ?, meta_description = ?, updated_at = ? WHERE id = ?',
            [
                $translation['name'],
                $translation['name_short'],
                $translation['description'],
                $translation['meta_title'],
                $translation['meta_description'],
                $now,
                (int) $existing['id'],
            ]
        );
    }

    /**
     * Создаёт или обновляет постоянный редирект.
     */
    private function upsertRedirect(string $fromPath, string $toPath, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::REDIRECTS . ' WHERE from_path = ? LIMIT 1',
            [$fromPath]
        );

        if ($existing !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::REDIRECTS
                . ' SET to_path = ?, status_code = ?, is_active = ?, updated_at = ? WHERE id = ?',
                [$toPath, 301, 1, $now, (int) $existing['id']]
            );

            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::REDIRECTS
            . ' (from_path, to_path, status_code, is_active, hit_count, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$fromPath, $toPath, 301, 1, 0, $now, $now]
        );
    }

    /**
     * Возвращает порядок шифров в новой категории.
     *
     * @return array<string, int>
     */
    private function cipherSortOrders(): array
    {
        return [
            'a1z26' => 20,
            'polybius-square' => 30,
            'bacon' => 40,
        ];
    }

    /**
     * Возвращает прежний порядок шифров в классической категории.
     *
     * @return array<string, int>
     */
    private function originalCipherSortOrders(): array
    {
        return [
            'bacon' => 70,
            'a1z26' => 90,
            'polybius-square' => 100,
        ];
    }

    /**
     * Возвращает обновлённые переводы категории Codes & Alphabets.
     *
     * @return array<string, array{name: string, name_short: string, description: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Codes & Alphabets',
                'name_short' => 'Codes & Alphabets',
                'description' => 'Tools for working with communication codes, symbolic alphabets, letter-number notation, coordinate alphabets, and character-based systems such as Morse code, A1Z26, Polybius Square, and Bacon cipher.',
                'meta_title' => 'Codes & Alphabets | Ciphers Online',
                'meta_description' => 'Explore online tools for communication codes and symbolic alphabets, including Morse code, A1Z26, Polybius Square, and Bacon cipher.',
            ],
            'ru' => [
                'name' => 'Коды и алфавиты',
                'name_short' => 'Коды и алфавиты',
                'description' => 'Инструменты для работы с коммуникационными кодами, символьными алфавитами, буквенно-числовой записью, координатными алфавитами и системами вроде азбуки Морзе, A1Z26, квадрата Полибия и шифра Бэкона.',
                'meta_title' => 'Коды и алфавиты | Ciphers Online',
                'meta_description' => 'Онлайн-инструменты для коммуникационных кодов и символьных алфавитов, включая азбуку Морзе, A1Z26, квадрат Полибия и шифр Бэкона.',
            ],
            'de' => [
                'name' => 'Codes & Alphabete',
                'name_short' => 'Codes & Alphabete',
                'description' => 'Tools für Kommunikationscodes, symbolische Alphabete, Buchstaben-Zahlen-Notation, Koordinatenalphabete und zeichengestützte Systeme wie Morsecode, A1Z26, Polybius-Quadrat und Bacon-Chiffre.',
                'meta_title' => 'Codes & Alphabete | Ciphers Online',
                'meta_description' => 'Online-Tools für Kommunikationscodes und symbolische Alphabete, darunter Morsecode, A1Z26, Polybius-Quadrat und Bacon-Chiffre.',
            ],
            'es' => [
                'name' => 'Códigos y alfabetos',
                'name_short' => 'Códigos y alfabetos',
                'description' => 'Herramientas para códigos de comunicación, alfabetos simbólicos, notación letra-número, alfabetos de coordenadas y sistemas basados en caracteres como código Morse, A1Z26, cuadrado de Polybius y cifrado Bacon.',
                'meta_title' => 'Códigos y alfabetos | Ciphers Online',
                'meta_description' => 'Herramientas online para códigos de comunicación y alfabetos simbólicos, incluidos código Morse, A1Z26, cuadrado de Polybius y cifrado Bacon.',
            ],
            'fr' => [
                'name' => 'Codes et alphabets',
                'name_short' => 'Codes et alphabets',
                'description' => 'Outils pour les codes de communication, les alphabets symboliques, la notation lettre-nombre, les alphabets à coordonnées et les systèmes fondés sur les caractères comme le code Morse, A1Z26, le carré de Polybe et le chiffre de Bacon.',
                'meta_title' => 'Codes et alphabets | Ciphers Online',
                'meta_description' => 'Outils en ligne pour les codes de communication et les alphabets symboliques, avec code Morse, A1Z26, carré de Polybe et chiffre de Bacon.',
            ],
            'it' => [
                'name' => 'Codici e alfabeti',
                'name_short' => 'Codici e alfabeti',
                'description' => 'Strumenti per codici di comunicazione, alfabeti simbolici, notazione lettera-numero, alfabeti a coordinate e sistemi basati sui caratteri come codice Morse, A1Z26, quadrato di Polybius e cifrario Bacon.',
                'meta_title' => 'Codici e alfabeti | Ciphers Online',
                'meta_description' => 'Strumenti online per codici di comunicazione e alfabeti simbolici, inclusi codice Morse, A1Z26, quadrato di Polybius e cifrario Bacon.',
            ],
            'pt' => [
                'name' => 'Códigos e alfabetos',
                'name_short' => 'Códigos e alfabetos',
                'description' => 'Ferramentas para códigos de comunicação, alfabetos simbólicos, notação letra-número, alfabetos de coordenadas e sistemas baseados em caracteres como código Morse, A1Z26, quadrado de Polybius e cifra de Bacon.',
                'meta_title' => 'Códigos e alfabetos | Ciphers Online',
                'meta_description' => 'Ferramentas online para códigos de comunicação e alfabetos simbólicos, incluindo código Morse, A1Z26, quadrado de Polybius e cifra de Bacon.',
            ],
            'tr' => [
                'name' => 'Kodlar ve alfabeler',
                'name_short' => 'Kodlar ve alfabeler',
                'description' => 'Mors alfabesi, A1Z26, Polybius karesi ve Bacon şifresi gibi iletişim kodları, sembolik alfabeler, harf-sayı gösterimi, koordinat alfabeleri ve karakter tabanlı sistemler için araçlar.',
                'meta_title' => 'Kodlar ve alfabeler | Ciphers Online',
                'meta_description' => 'Mors alfabesi, A1Z26, Polybius karesi ve Bacon şifresi dahil iletişim kodları ve sembolik alfabeler için çevrimiçi araçlar.',
            ],
        ];
    }

    /**
     * Возвращает редиректы со старых URL перенесённых инструментов.
     *
     * @return array<int, array{0:string, 1:string}>
     */
    private function redirects(): array
    {
        $aliases = ['a1z26', 'polybius-square', 'bacon'];
        $locales = ['', '/en', '/ru', '/de', '/es', '/fr', '/it', '/pt', '/tr'];
        $redirects = [];

        foreach ($aliases as $alias) {
            foreach ($locales as $locale) {
                $redirects[] = [
                    $locale . '/classical-ciphers/' . $alias,
                    $locale . '/codes-and-alphabets/' . $alias,
                ];
            }
        }

        return $redirects;
    }
}
