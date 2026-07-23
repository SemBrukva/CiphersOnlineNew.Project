<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Создаёт категорию «Утилиты и генераторы» — раздел инструментов-магнитов
 * (генераторы UUID, паролей, случайных строк и т. п.).
 */
class CreateUtilitiesCategory extends Migration
{
    /**
     * Создаёт категорию utilities с переводами на 8 языков.
     */
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->db->transaction(function () use ($now): void {
            $categoryId = $this->upsertCategory($now);

            foreach ($this->translations() as $language => $translation) {
                $this->upsertTranslation($categoryId, $language, $translation, $now);
            }
        });
    }

    /**
     * Удаляет категорию и все её переводы.
     */
    public function down(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['utilities']
        );

        if ($category === false) {
            return;
        }

        $this->db->execute(
            'DELETE FROM ' . Tables::CIPHER_CATEGORY_TRANSLATIONS . ' WHERE category_id = ?',
            [(int) $category['id']]
        );

        $this->db->execute(
            'DELETE FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE id = ?',
            [(int) $category['id']]
        );
    }

    /**
     * Создаёт или обновляет запись категории. Группа `category` = 'encoding'
     * (не-шифровой раздел; CHECK допускает только 'cipher'|'encoding').
     */
    private function upsertCategory(string $now): int
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['utilities']
        );

        if ($category !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHER_CATEGORIES
                . ' SET category = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                ['encoding', 50, 1, $now, (int) $category['id']]
            );

            return (int) $category['id'];
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHER_CATEGORIES
            . ' (alias, category, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            ['utilities', 'encoding', 50, 1, $now, $now]
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
     * Возвращает переводы категории на 8 языков.
     *
     * @return array<string, array{name: string, name_short: string, description: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'             => 'Utilities & Generators',
                'name_short'       => 'Utilities',
                'description'      => 'Handy developer utilities and generators: UUID/GUID, random strings, passwords and more. Fast, free and processed entirely in your browser.',
                'meta_title'       => 'Developer Utilities & Generators | Ciphers Online',
                'meta_description' => 'Free online developer utilities: UUID/GUID generator, random strings, passwords and more. Everything runs locally in your browser.',
            ],
            'ru' => [
                'name'             => 'Утилиты и генераторы',
                'name_short'       => 'Утилиты',
                'description'      => 'Полезные утилиты и генераторы для разработчиков: UUID/GUID, случайные строки, пароли и другое. Быстро, бесплатно и целиком в вашем браузере.',
                'meta_title'       => 'Утилиты и генераторы для разработчиков | Ciphers Online',
                'meta_description' => 'Бесплатные онлайн-утилиты для разработчиков: генератор UUID/GUID, случайные строки, пароли и другое. Всё работает локально в браузере.',
            ],
            'de' => [
                'name'             => 'Werkzeuge & Generatoren',
                'name_short'       => 'Werkzeuge',
                'description'      => 'Praktische Entwickler-Werkzeuge und Generatoren: UUID/GUID, Zufallszeichenketten, Passwörter und mehr. Schnell, kostenlos und vollständig im Browser.',
                'meta_title'       => 'Entwickler-Werkzeuge & Generatoren | Ciphers Online',
                'meta_description' => 'Kostenlose Online-Werkzeuge für Entwickler: UUID/GUID-Generator, Zufallszeichenketten, Passwörter und mehr. Alles läuft lokal im Browser.',
            ],
            'es' => [
                'name'             => 'Utilidades y generadores',
                'name_short'       => 'Utilidades',
                'description'      => 'Utilidades y generadores prácticos para desarrolladores: UUID/GUID, cadenas aleatorias, contraseñas y más. Rápido, gratis y procesado íntegramente en tu navegador.',
                'meta_title'       => 'Utilidades y generadores para desarrolladores | Ciphers Online',
                'meta_description' => 'Utilidades online gratuitas para desarrolladores: generador de UUID/GUID, cadenas aleatorias, contraseñas y más. Todo se ejecuta localmente en tu navegador.',
            ],
            'fr' => [
                'name'             => 'Utilitaires et générateurs',
                'name_short'       => 'Utilitaires',
                'description'      => 'Utilitaires et générateurs pratiques pour développeurs : UUID/GUID, chaînes aléatoires, mots de passe et plus. Rapide, gratuit et traité entièrement dans votre navigateur.',
                'meta_title'       => 'Utilitaires et générateurs pour développeurs | Ciphers Online',
                'meta_description' => 'Utilitaires en ligne gratuits pour développeurs : générateur UUID/GUID, chaînes aléatoires, mots de passe et plus. Tout s\'exécute localement dans votre navigateur.',
            ],
            'it' => [
                'name'             => 'Utilità e generatori',
                'name_short'       => 'Utilità',
                'description'      => 'Utilità e generatori pratici per sviluppatori: UUID/GUID, stringhe casuali, password e altro. Veloce, gratuito ed elaborato interamente nel browser.',
                'meta_title'       => 'Utilità e generatori per sviluppatori | Ciphers Online',
                'meta_description' => 'Utilità online gratuite per sviluppatori: generatore UUID/GUID, stringhe casuali, password e altro. Tutto viene eseguito localmente nel browser.',
            ],
            'pt' => [
                'name'             => 'Utilitários e geradores',
                'name_short'       => 'Utilitários',
                'description'      => 'Utilitários e geradores práticos para programadores: UUID/GUID, strings aleatórias, senhas e mais. Rápido, grátis e processado inteiramente no seu navegador.',
                'meta_title'       => 'Utilitários e geradores para programadores | Ciphers Online',
                'meta_description' => 'Utilitários online grátis para programadores: gerador de UUID/GUID, strings aleatórias, senhas e mais. Tudo roda localmente no seu navegador.',
            ],
            'tr' => [
                'name'             => 'Araçlar ve üreteçler',
                'name_short'       => 'Araçlar',
                'description'      => 'Geliştiriciler için kullanışlı araçlar ve üreteçler: UUID/GUID, rastgele dizeler, parolalar ve daha fazlası. Hızlı, ücretsiz ve tamamen tarayıcınızda işlenir.',
                'meta_title'       => 'Geliştirici Araçları ve Üreteçleri | Ciphers Online',
                'meta_description' => 'Geliştiriciler için ücretsiz çevrimiçi araçlar: UUID/GUID üreteci, rastgele dizeler, parolalar ve daha fazlası. Her şey tarayıcınızda yerel olarak çalışır.',
            ],
        ];
    }
}
