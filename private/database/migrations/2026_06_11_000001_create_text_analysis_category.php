<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Создаёт категорию «Анализ текста и криптоанализ».
 */
class CreateTextAnalysisCategory extends Migration
{
    /**
     * Создаёт категорию Text Analysis & Cryptanalysis с переводами.
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
            ['text-analysis']
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
            ['text-analysis']
        );

        if ($category !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHER_CATEGORIES
                . ' SET category = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                ['encoding', 30, 1, $now, (int) $category['id']]
            );

            return (int) $category['id'];
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHER_CATEGORIES
            . ' (alias, category, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            ['text-analysis', 'encoding', 30, 1, $now, $now]
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
                'name'             => 'Text Analysis & Cryptanalysis',
                'name_short'       => 'Text Analysis',
                'description'      => 'Tools for analyzing text using frequency analysis, statistical methods, and cryptanalysis techniques. Identify letter distributions, detect language patterns, and break classical ciphers.',
                'meta_title'       => 'Text Analysis & Cryptanalysis | Ciphers Online',
                'meta_description' => 'Online tools for text analysis and cryptanalysis. Analyze letter frequencies, detect patterns, and break classical ciphers with statistical methods.',
            ],
            'ru' => [
                'name'             => 'Анализ текста и криптоанализ',
                'name_short'       => 'Анализ текста',
                'description'      => 'Инструменты для анализа текста: частотный анализ, статистические методы и техники криптоанализа. Определяйте распределение букв, обнаруживайте языковые паттерны и взламывайте классические шифры.',
                'meta_title'       => 'Анализ текста и криптоанализ | Ciphers Online',
                'meta_description' => 'Онлайн-инструменты для анализа текста и криптоанализа. Анализируйте частоты букв, обнаруживайте паттерны и взламывайте классические шифры.',
            ],
            'de' => [
                'name'             => 'Textanalyse & Kryptoanalyse',
                'name_short'       => 'Textanalyse',
                'description'      => 'Tools zur Textanalyse mit Häufigkeitsanalyse, statistischen Methoden und kryptoanalytischen Techniken. Buchstabenverteilungen erkennen und klassische Chiffren knacken.',
                'meta_title'       => 'Textanalyse & Kryptoanalyse | Ciphers Online',
                'meta_description' => 'Online-Tools für Textanalyse und Kryptoanalyse. Buchstabenhäufigkeiten analysieren und klassische Verschlüsselungen entschlüsseln.',
            ],
            'es' => [
                'name'             => 'Análisis de texto y criptoanálisis',
                'name_short'       => 'Análisis de texto',
                'description'      => 'Herramientas para analizar texto mediante análisis de frecuencias, métodos estadísticos y técnicas de criptoanálisis. Identifica distribuciones de letras y descifra cifrados clásicos.',
                'meta_title'       => 'Análisis de texto y criptoanálisis | Ciphers Online',
                'meta_description' => 'Herramientas online para análisis de texto y criptoanálisis. Analiza frecuencias de letras y descifra cifrados clásicos con métodos estadísticos.',
            ],
            'fr' => [
                'name'             => 'Analyse de texte et cryptanalyse',
                'name_short'       => 'Analyse de texte',
                'description'      => 'Outils d\'analyse de texte par analyse de fréquences, méthodes statistiques et techniques de cryptanalyse. Identifiez les distributions de lettres et cassez les chiffres classiques.',
                'meta_title'       => 'Analyse de texte et cryptanalyse | Ciphers Online',
                'meta_description' => 'Outils en ligne pour l\'analyse de texte et la cryptanalyse. Analysez les fréquences des lettres et brisez les chiffrements classiques.',
            ],
            'it' => [
                'name'             => 'Analisi del testo e crittoanalisi',
                'name_short'       => 'Analisi del testo',
                'description'      => 'Strumenti per l\'analisi del testo tramite analisi delle frequenze, metodi statistici e tecniche di crittoanalisi. Identifica distribuzioni di lettere e decifra cifrari classici.',
                'meta_title'       => 'Analisi del testo e crittoanalisi | Ciphers Online',
                'meta_description' => 'Strumenti online per l\'analisi del testo e la crittoanalisi. Analizza le frequenze delle lettere e rompi i cifrari classici.',
            ],
            'pt' => [
                'name'             => 'Análise de texto e criptoanálise',
                'name_short'       => 'Análise de texto',
                'description'      => 'Ferramentas para analisar texto usando análise de frequência, métodos estatísticos e técnicas de criptoanálise. Identifique distribuições de letras e quebre cifras clássicas.',
                'meta_title'       => 'Análise de texto e criptoanálise | Ciphers Online',
                'meta_description' => 'Ferramentas online para análise de texto e criptoanálise. Analise frequências de letras e quebre cifras clássicas com métodos estatísticos.',
            ],
            'tr' => [
                'name'             => 'Metin analizi ve kriptoanaliz',
                'name_short'       => 'Metin analizi',
                'description'      => 'Frekans analizi, istatistiksel yöntemler ve kriptoanaliz teknikleriyle metin analizi araçları. Harf dağılımlarını tespit edin ve klasik şifreleri kırın.',
                'meta_title'       => 'Metin analizi ve kriptoanaliz | Ciphers Online',
                'meta_description' => 'Metin analizi ve kriptoanaliz için çevrimiçi araçlar. Harf frekanslarını analiz edin ve istatistiksel yöntemlerle klasik şifreleri çözün.',
            ],
        ];
    }
}
