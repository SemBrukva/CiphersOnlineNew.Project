<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Заполняет переводы категории классических шифров на всех языках.
 */
class SeedClassicalCipherCategoryTranslations extends Migration
{
    /**
     * Создаёт или обновляет переводы категории classical-ciphers.
     */
    public function up(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['classical-ciphers']
        );

        if ($category === false) {
            return;
        }

        $categoryId = (int) $category['id'];
        $now = date('Y-m-d H:i:s');

        foreach ($this->translations() as $language => $translation) {
            $this->upsertTranslation($categoryId, $language, $translation, $now);
        }
    }

    /**
     * Откатывает переводы категории classical-ciphers к исходному состоянию.
     */
    public function down(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['classical-ciphers']
        );

        if ($category === false) {
            return;
        }

        $categoryId = (int) $category['id'];

        $this->db->execute(
            'DELETE FROM ' . Tables::CIPHER_CATEGORY_TRANSLATIONS
            . ' WHERE category_id = ? AND language IN (?, ?, ?, ?, ?, ?)',
            [$categoryId, 'de', 'es', 'fr', 'it', 'pt', 'tr']
        );

        foreach (['en', 'ru'] as $language) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHER_CATEGORY_TRANSLATIONS
                . ' SET name = ?, name_short = ?, description = ?, meta_title = ?, meta_description = ?, updated_at = ? '
                . 'WHERE category_id = ? AND language = ?',
                ['Classical Ciphers', '', '', '', '', date('Y-m-d H:i:s'), $categoryId, $language]
            );
        }
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
            . ' SET name = ?, name_short = ?, description = ?, meta_title = ?, meta_description = ?, updated_at = ? '
            . 'WHERE id = ?',
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
     * Возвращает локализованные данные категории классических шифров.
     *
     * @return array<string, array{name: string, name_short: string, description: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Classical Ciphers',
                'name_short' => 'Classical',
                'description' => 'Tools for encrypting, decrypting, and learning historical substitution and transposition ciphers, including Caesar and other classic methods.',
                'meta_title' => 'Classical Ciphers | Ciphers Online',
                'meta_description' => 'Classical cipher tools hub: Caesar cipher and other historical encryption methods for learning and practice.',
            ],
            'ru' => [
                'name' => 'Классические шифры',
                'name_short' => 'Классические',
                'description' => 'Инструменты для шифрования, расшифровки и изучения исторических шифров замены и перестановки, включая шифр Цезаря и другие классические методы.',
                'meta_title' => 'Классические шифры | Ciphers Online',
                'meta_description' => 'Hub классических шифров: шифр Цезаря и другие исторические методы шифрования для обучения и практики.',
            ],
            'de' => [
                'name' => 'Klassische Chiffren',
                'name_short' => 'Klassisch',
                'description' => 'Tools zum Verschlüsseln, Entschlüsseln und Lernen historischer Substitutions- und Transpositionschiffren, einschließlich Caesar und anderer klassischer Methoden.',
                'meta_title' => 'Klassische Chiffren | Ciphers Online',
                'meta_description' => 'Hub für klassische Chiffren: Caesar-Chiffre und andere historische Verschlüsselungsmethoden zum Lernen und Üben.',
            ],
            'es' => [
                'name' => 'Cifrados clásicos',
                'name_short' => 'Clásicos',
                'description' => 'Herramientas para cifrar, descifrar y aprender cifrados históricos de sustitución y transposición, incluido César y otros métodos clásicos.',
                'meta_title' => 'Cifrados clásicos | Ciphers Online',
                'meta_description' => 'Hub de cifrados clásicos: cifrado César y otros métodos históricos de cifrado para aprender y practicar.',
            ],
            'fr' => [
                'name' => 'Chiffres classiques',
                'name_short' => 'Classiques',
                'description' => 'Outils pour chiffrer, déchiffrer et apprendre les chiffres historiques par substitution et transposition, dont César et d’autres méthodes classiques.',
                'meta_title' => 'Chiffres classiques | Ciphers Online',
                'meta_description' => 'Hub des chiffres classiques : chiffre de César et autres méthodes historiques de chiffrement pour apprendre et pratiquer.',
            ],
            'it' => [
                'name' => 'Cifrari classici',
                'name_short' => 'Classici',
                'description' => 'Strumenti per cifrare, decifrare e studiare cifrari storici a sostituzione e trasposizione, incluso Cesare e altri metodi classici.',
                'meta_title' => 'Cifrari classici | Ciphers Online',
                'meta_description' => 'Hub dei cifrari classici: cifrario di Cesare e altri metodi storici di crittografia per studio e pratica.',
            ],
            'pt' => [
                'name' => 'Cifras clássicas',
                'name_short' => 'Clássicas',
                'description' => 'Ferramentas para cifrar, decifrar e aprender cifras históricas de substituição e transposição, incluindo César e outros métodos clássicos.',
                'meta_title' => 'Cifras clássicas | Ciphers Online',
                'meta_description' => 'Hub de cifras clássicas: cifra de César e outros métodos históricos de criptografia para aprender e praticar.',
            ],
            'tr' => [
                'name' => 'Klasik şifreler',
                'name_short' => 'Klasik',
                'description' => 'Sezar dahil klasik yöntemlerle tarihsel yerine koyma ve yer değiştirme şifrelerini şifrelemek, çözmek ve öğrenmek için araçlar.',
                'meta_title' => 'Klasik şifreler | Ciphers Online',
                'meta_description' => 'Klasik şifre araçları merkezi: öğrenme ve pratik için Sezar şifresi ve diğer tarihsel şifreleme yöntemleri.',
            ],
        ];
    }
}
