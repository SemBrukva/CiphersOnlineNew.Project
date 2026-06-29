<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Создаёт категорию «Хеширование и криптография».
 */
class CreateHashingCategory extends Migration
{
    /**
     * Создаёт категорию Hashing & Cryptography с переводами.
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
            ['hashing']
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
     * Создаёт или обновляет запись категории.
     */
    private function upsertCategory(string $now): int
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['hashing']
        );

        if ($category !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHER_CATEGORIES
                . ' SET category = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                ['encoding', 40, 1, $now, (int) $category['id']]
            );

            return (int) $category['id'];
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHER_CATEGORIES
            . ' (alias, category, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            ['hashing', 'encoding', 40, 1, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод категории.
     *
     * @param array{name: string, name_short: string, description: string, meta_title: string, meta_description: string} $translation
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
     * Возвращает переводы категории.
     *
     * @return array<string, array{name: string, name_short: string, description: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'             => 'Hashing & Cryptography',
                'name_short'       => 'Hashing',
                'description'      => 'Generate cryptographic hashes of text and files: MD5, SHA-1, SHA-256, SHA-512, SHA-3, CRC32 and more. One-way fingerprints for integrity checks, password storage, and digital signatures.',
                'meta_title'       => 'Hashing & Cryptography Tools | Ciphers Online',
                'meta_description' => 'Free online hash generators: MD5, SHA-1, SHA-256, SHA-512, SHA-3, CRC32. Calculate fingerprints in your browser for integrity, signatures, and verification.',
            ],
            'ru' => [
                'name'             => 'Хеширование и криптография',
                'name_short'       => 'Хеширование',
                'description'      => 'Вычисление криптографических хешей текста и файлов: MD5, SHA-1, SHA-256, SHA-512, SHA-3, CRC32 и другие. Односторонние отпечатки для проверки целостности, хранения паролей и цифровых подписей.',
                'meta_title'       => 'Хеширование и криптография онлайн | Ciphers Online',
                'meta_description' => 'Бесплатные генераторы хешей онлайн: MD5, SHA-1, SHA-256, SHA-512, SHA-3, CRC32. Вычисление отпечатков прямо в браузере для проверки целостности и подписей.',
            ],
            'de' => [
                'name'             => 'Hashing & Kryptografie',
                'name_short'       => 'Hashing',
                'description'      => 'Kryptografische Hashes von Texten und Dateien berechnen: MD5, SHA-1, SHA-256, SHA-512, SHA-3, CRC32 und mehr. Einweg-Fingerabdrücke für Integritätsprüfungen, Passwortspeicherung und digitale Signaturen.',
                'meta_title'       => 'Hashing & Kryptografie Tools | Ciphers Online',
                'meta_description' => 'Kostenlose Online-Hash-Generatoren: MD5, SHA-1, SHA-256, SHA-512, SHA-3, CRC32. Fingerabdrücke direkt im Browser berechnen.',
            ],
            'es' => [
                'name'             => 'Hash y criptografía',
                'name_short'       => 'Hash',
                'description'      => 'Genera hashes criptográficos de texto y archivos: MD5, SHA-1, SHA-256, SHA-512, SHA-3, CRC32 y más. Huellas digitales unidireccionales para integridad, contraseñas y firmas digitales.',
                'meta_title'       => 'Herramientas de hash y criptografía | Ciphers Online',
                'meta_description' => 'Generadores de hash online gratis: MD5, SHA-1, SHA-256, SHA-512, SHA-3, CRC32. Calcula huellas digitales directamente en el navegador.',
            ],
            'fr' => [
                'name'             => 'Hachage et cryptographie',
                'name_short'       => 'Hachage',
                'description'      => 'Générez des hachages cryptographiques de texte et de fichiers : MD5, SHA-1, SHA-256, SHA-512, SHA-3, CRC32 et plus. Empreintes à sens unique pour vérification d\'intégrité, mots de passe et signatures.',
                'meta_title'       => 'Outils de hachage et cryptographie | Ciphers Online',
                'meta_description' => 'Générateurs de hachage en ligne gratuits : MD5, SHA-1, SHA-256, SHA-512, SHA-3, CRC32. Calcul d\'empreintes directement dans votre navigateur.',
            ],
            'it' => [
                'name'             => 'Hashing e crittografia',
                'name_short'       => 'Hashing',
                'description'      => 'Genera hash crittografici di testo e file: MD5, SHA-1, SHA-256, SHA-512, SHA-3, CRC32 e altri. Impronte unidirezionali per verifiche di integrità, password e firme digitali.',
                'meta_title'       => 'Strumenti di hashing e crittografia | Ciphers Online',
                'meta_description' => 'Generatori di hash online gratuiti: MD5, SHA-1, SHA-256, SHA-512, SHA-3, CRC32. Calcola impronte direttamente nel browser.',
            ],
            'pt' => [
                'name'             => 'Hash e criptografia',
                'name_short'       => 'Hash',
                'description'      => 'Gere hashes criptográficos de texto e arquivos: MD5, SHA-1, SHA-256, SHA-512, SHA-3, CRC32 e mais. Impressões unidirecionais para verificação de integridade, senhas e assinaturas digitais.',
                'meta_title'       => 'Ferramentas de hash e criptografia | Ciphers Online',
                'meta_description' => 'Geradores de hash online grátis: MD5, SHA-1, SHA-256, SHA-512, SHA-3, CRC32. Calcule impressões digitais direto no navegador.',
            ],
            'tr' => [
                'name'             => 'Karma ve kriptografi',
                'name_short'       => 'Karma',
                'description'      => 'Metin ve dosyaların kriptografik karma değerlerini hesaplayın: MD5, SHA-1, SHA-256, SHA-512, SHA-3, CRC32 ve daha fazlası. Bütünlük, parola ve dijital imza için tek yönlü parmak izleri.',
                'meta_title'       => 'Karma ve kriptografi araçları | Ciphers Online',
                'meta_description' => 'Ücretsiz çevrimiçi karma üreticileri: MD5, SHA-1, SHA-256, SHA-512, SHA-3, CRC32. Parmak izlerini doğrudan tarayıcıda hesaplayın.',
            ],
        ];
    }
}
