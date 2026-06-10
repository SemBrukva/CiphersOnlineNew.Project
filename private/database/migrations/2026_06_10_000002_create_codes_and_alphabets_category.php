<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Создаёт категорию кодов и алфавитов и переносит в неё азбуку Морзе.
 */
class CreateCodesAndAlphabetsCategory extends Migration
{
    /**
     * Создаёт категорию Codes & Alphabets, переносит Morse Code и добавляет редиректы.
     */
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->db->transaction(function () use ($now): void {
            $categoryId = $this->upsertCategory($now);

            foreach ($this->translations() as $language => $translation) {
                $this->upsertTranslation($categoryId, $language, $translation, $now);
            }

            $this->moveMorseCode($categoryId, $now);

            foreach ($this->redirects() as [$fromPath, $toPath]) {
                $this->upsertRedirect($fromPath, $toPath, $now);
            }
        });
    }

    /**
     * Возвращает азбуку Морзе в классические шифры и удаляет созданную категорию.
     */
    public function down(): void
    {
        $this->db->transaction(function (): void {
            $classical = $this->db->fetch(
                'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
                ['classical-ciphers']
            );

            if ($classical !== false) {
                $this->db->execute(
                    'UPDATE ' . Tables::CIPHERS . ' SET category_id = ?, updated_at = ? WHERE alias = ?',
                    [(int) $classical['id'], date('Y-m-d H:i:s'), 'morse-code']
                );
            }

            foreach ($this->redirects() as [$fromPath]) {
                $this->db->execute('DELETE FROM ' . Tables::REDIRECTS . ' WHERE from_path = ?', [$fromPath]);
            }

            $category = $this->db->fetch(
                'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
                ['codes-and-alphabets']
            );

            if ($category !== false) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE id = ?',
                    [(int) $category['id']]
                );
            }
        });
    }

    /**
     * Создаёт или обновляет запись категории.
     */
    private function upsertCategory(string $now): int
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['codes-and-alphabets']
        );

        if ($category !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHER_CATEGORIES
                . ' SET category = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                ['encoding', 20, 1, $now, (int) $category['id']]
            );

            return (int) $category['id'];
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHER_CATEGORIES
            . ' (alias, category, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            ['codes-and-alphabets', 'encoding', 20, 1, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод категории.
     *
     * @param array{name: string, name_short: string, description: string, meta_title: string, meta_description: string} $translation Данные перевода.
     */
    private function upsertTranslation(int $categoryId, string $language, array $translation, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORY_TRANSLATIONS
            . ' WHERE category_id = ? AND language = ? LIMIT 1',
            [$categoryId, $language]
        );

        if ($existing !== false) {
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

            return;
        }

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
    }

    /**
     * Переносит азбуку Морзе в новую категорию.
     */
    private function moveMorseCode(int $categoryId, string $now): void
    {
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS . ' SET category_id = ?, sort_order = ?, updated_at = ? WHERE alias = ?',
            [$categoryId, 10, $now, 'morse-code']
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
     * Возвращает переводы категории Codes & Alphabets.
     *
     * @return array<string, array{name: string, name_short: string, description: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Codes & Alphabets',
                'name_short' => 'Codes & Alphabets',
                'description' => 'Tools for working with communication codes, symbolic alphabets, and character-based notation systems such as Morse code.',
                'meta_title' => 'Codes & Alphabets | Ciphers Online',
                'meta_description' => 'Explore online tools for communication codes and symbolic alphabets, including Morse code translation and playback.',
            ],
            'ru' => [
                'name' => 'Коды и алфавиты',
                'name_short' => 'Коды и алфавиты',
                'description' => 'Инструменты для работы с коммуникационными кодами, символьными алфавитами и системами записи символов, включая азбуку Морзе.',
                'meta_title' => 'Коды и алфавиты | Ciphers Online',
                'meta_description' => 'Онлайн-инструменты для коммуникационных кодов и символьных алфавитов, включая перевод и воспроизведение азбуки Морзе.',
            ],
            'de' => [
                'name' => 'Codes & Alphabete',
                'name_short' => 'Codes & Alphabete',
                'description' => 'Tools für Kommunikationscodes, symbolische Alphabete und zeichenbasierte Notationssysteme wie Morsecode.',
                'meta_title' => 'Codes & Alphabete | Ciphers Online',
                'meta_description' => 'Online-Tools für Kommunikationscodes und symbolische Alphabete, einschließlich Morsecode-Übersetzung und Wiedergabe.',
            ],
            'es' => [
                'name' => 'Códigos y alfabetos',
                'name_short' => 'Códigos y alfabetos',
                'description' => 'Herramientas para trabajar con códigos de comunicación, alfabetos simbólicos y sistemas de notación basados en caracteres como el código Morse.',
                'meta_title' => 'Códigos y alfabetos | Ciphers Online',
                'meta_description' => 'Herramientas online para códigos de comunicación y alfabetos simbólicos, incluida la traducción y reproducción del código Morse.',
            ],
            'fr' => [
                'name' => 'Codes et alphabets',
                'name_short' => 'Codes et alphabets',
                'description' => 'Outils pour travailler avec des codes de communication, des alphabets symboliques et des systèmes de notation fondés sur les caractères, comme le code Morse.',
                'meta_title' => 'Codes et alphabets | Ciphers Online',
                'meta_description' => 'Outils en ligne pour les codes de communication et les alphabets symboliques, avec traduction et lecture du code Morse.',
            ],
            'it' => [
                'name' => 'Codici e alfabeti',
                'name_short' => 'Codici e alfabeti',
                'description' => 'Strumenti per lavorare con codici di comunicazione, alfabeti simbolici e sistemi di notazione basati sui caratteri, come il codice Morse.',
                'meta_title' => 'Codici e alfabeti | Ciphers Online',
                'meta_description' => 'Strumenti online per codici di comunicazione e alfabeti simbolici, inclusa la traduzione e riproduzione del codice Morse.',
            ],
            'pt' => [
                'name' => 'Códigos e alfabetos',
                'name_short' => 'Códigos e alfabetos',
                'description' => 'Ferramentas para trabalhar com códigos de comunicação, alfabetos simbólicos e sistemas de notação baseados em caracteres, como o código Morse.',
                'meta_title' => 'Códigos e alfabetos | Ciphers Online',
                'meta_description' => 'Ferramentas online para códigos de comunicação e alfabetos simbólicos, incluindo tradução e reprodução de código Morse.',
            ],
            'tr' => [
                'name' => 'Kodlar ve alfabeler',
                'name_short' => 'Kodlar ve alfabeler',
                'description' => 'Mors alfabesi gibi iletişim kodları, sembolik alfabeler ve karakter tabanlı gösterim sistemleriyle çalışmak için araçlar.',
                'meta_title' => 'Kodlar ve alfabeler | Ciphers Online',
                'meta_description' => 'Mors alfabesi çevirisi ve oynatma dahil iletişim kodları ve sembolik alfabeler için çevrimiçi araçlar.',
            ],
        ];
    }

    /**
     * Возвращает редиректы со старого URL азбуки Морзе.
     *
     * @return array<int, array{0:string, 1:string}>
     */
    private function redirects(): array
    {
        return [
            ['/classical-ciphers/morse-code', '/codes-and-alphabets/morse-code'],
            ['/en/classical-ciphers/morse-code', '/en/codes-and-alphabets/morse-code'],
            ['/ru/classical-ciphers/morse-code', '/ru/codes-and-alphabets/morse-code'],
            ['/de/classical-ciphers/morse-code', '/de/codes-and-alphabets/morse-code'],
            ['/es/classical-ciphers/morse-code', '/es/codes-and-alphabets/morse-code'],
            ['/fr/classical-ciphers/morse-code', '/fr/codes-and-alphabets/morse-code'],
            ['/it/classical-ciphers/morse-code', '/it/codes-and-alphabets/morse-code'],
            ['/pt/classical-ciphers/morse-code', '/pt/codes-and-alphabets/morse-code'],
            ['/tr/classical-ciphers/morse-code', '/tr/codes-and-alphabets/morse-code'],
        ];
    }
}
